<?php

namespace justinholtweb\rabbits\services;

use Craft;
use craft\base\Component;
use justinholtweb\rabbits\records\TokenRecord;

/**
 * Bridge between Rabbits and Sourdough theme system
 *
 * When Sourdough is installed, reads design tokens from the active theme.
 * When Sourdough is not installed, uses Rabbits' own token system.
 */
class ThemeBridge extends Component
{
    /**
     * Check if Sourdough plugin is installed and enabled
     */
    public function isSourdoughInstalled(): bool
    {
        return Craft::$app->getPlugins()->isPluginInstalled('sourdough')
            && Craft::$app->getPlugins()->isPluginEnabled('sourdough');
    }

    /**
     * Get all available design tokens
     */
    public function getTokens(): array
    {
        if ($this->isSourdoughInstalled()) {
            return $this->getSourdoughTokens();
        }

        return $this->getRabbitsTokens();
    }

    /**
     * Get tokens grouped by category
     */
    public function getTokensByCategory(): array
    {
        $tokens = $this->getTokens();
        $grouped = [];

        foreach ($tokens as $token) {
            $category = $token['category'] ?? 'general';
            $grouped[$category][] = $token;
        }

        return $grouped;
    }

    /**
     * Get a specific token value
     */
    public function getTokenValue(string $handle): ?string
    {
        $tokens = $this->getTokens();

        foreach ($tokens as $token) {
            if ($token['handle'] === $handle) {
                return $token['value'];
            }
        }

        return null;
    }

    /**
     * Read tokens from the active Sourdough theme's tokens.css
     */
    private function getSourdoughTokens(): array
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('sourdough');

        if (!$plugin) {
            return [];
        }

        $settings = $plugin->getSettings();
        $activeTheme = $settings->activeTheme ?? null;

        if (!$activeTheme) {
            return $this->getRabbitsTokens();
        }

        // Attempt to read tokens.css from the active theme
        $themePath = Craft::getAlias('@root/templates/_themes/' . $activeTheme . '/assets/css/tokens.css');

        if (!$themePath || !file_exists($themePath)) {
            return $this->getRabbitsTokens();
        }

        return $this->parseTokensCss(file_get_contents($themePath));
    }

    /**
     * Parse CSS custom properties from a tokens.css file
     */
    private function parseTokensCss(string $css): array
    {
        $tokens = [];

        // Match CSS custom properties: --category-name: value;
        if (preg_match_all('/--([a-z0-9-]+):\s*([^;]+);/i', $css, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullHandle = $match[1];
                $value = trim($match[2]);

                // Split handle into category and name (e.g., "color-primary" → category: "color", name: "primary")
                $parts = explode('-', $fullHandle, 2);
                $category = $parts[0];
                $name = $parts[1] ?? $fullHandle;

                $tokens[] = [
                    'handle' => $fullHandle,
                    'category' => $category,
                    'label' => $this->handleToLabel($fullHandle),
                    'value' => $value,
                    'cssVar' => 'var(--' . $fullHandle . ')',
                    'source' => 'sourdough',
                ];
            }
        }

        return $tokens;
    }

    /**
     * Get tokens from Rabbits' own database table
     */
    private function getRabbitsTokens(): array
    {
        $records = TokenRecord::find()->orderBy(['category' => SORT_ASC, 'handle' => SORT_ASC])->all();
        $tokens = [];

        foreach ($records as $record) {
            $tokens[] = [
                'handle' => $record->handle,
                'category' => $record->category,
                'label' => $record->label,
                'value' => $record->value,
                'cssVar' => 'var(--' . $record->handle . ')',
                'source' => 'rabbits',
            ];
        }

        return $tokens;
    }

    /**
     * Save a Rabbits token
     */
    public function saveToken(string $category, string $handle, string $label, string $value): bool
    {
        $record = TokenRecord::findOne(['handle' => $handle]) ?? new TokenRecord();

        $record->category = $category;
        $record->handle = $handle;
        $record->label = $label;
        $record->value = $value;

        return $record->save();
    }

    /**
     * Delete a Rabbits token
     */
    public function deleteToken(string $handle): bool
    {
        $record = TokenRecord::findOne(['handle' => $handle]);
        return $record ? $record->delete() !== false : false;
    }

    /**
     * Generate CSS custom properties block from all tokens
     */
    public function generateTokensCss(): string
    {
        $tokens = $this->getTokens();

        if (empty($tokens)) {
            return '';
        }

        $lines = [':root {'];

        foreach ($tokens as $token) {
            $lines[] = '  --' . $token['handle'] . ': ' . $token['value'] . ';';
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * Convert a handle to a human-readable label
     */
    private function handleToLabel(string $handle): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $handle));
    }
}
