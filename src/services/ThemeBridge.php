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
     * Read tokens from the active Sourdough theme.
     *
     * Delegates to Sourdough's ThemeService, which owns the canonical tokens.css
     * location and parse format. Falls back to Rabbits' own tokens when Sourdough
     * has no active theme or the theme ships no tokens.
     */
    private function getSourdoughTokens(): array
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('sourdough');

        // Guard against older Sourdough versions without the public token API.
        if (!$plugin || !isset($plugin->themes) || !method_exists($plugin->themes, 'getActiveThemeTokens')) {
            return $this->getRabbitsTokens();
        }

        $tokens = $plugin->themes->getActiveThemeTokens();

        return !empty($tokens) ? $tokens : $this->getRabbitsTokens();
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
}
