<?php

namespace justinholtweb\rabbits\services;

use craft\base\Component;
use justinholtweb\rabbits\enums\AnimationTrigger;
use justinholtweb\rabbits\events\RegisterAnimationPresetsEvent;

/**
 * Processes animation configurations and generates frontend JS/CSS
 */
class AnimationManager extends Component
{
    /**
     * @event RegisterAnimationPresetsEvent Raised when collecting animation
     * presets, so listeners can add their own (or override built-ins).
     */
    public const EVENT_REGISTER_ANIMATION_PRESETS = 'registerAnimationPresets';

    /**
     * Get available animation presets
     */
    public function getPresets(): array
    {
        $presets = [
            'fade-in' => [
                'label' => 'Fade In',
                'keyframes' => [
                    ['offset' => 0, 'opacity' => 0],
                    ['offset' => 1, 'opacity' => 1],
                ],
                'options' => ['duration' => 600, 'easing' => 'ease-out'],
            ],
            'slide-up' => [
                'label' => 'Slide Up',
                'keyframes' => [
                    ['offset' => 0, 'opacity' => 0, 'transform' => 'translateY(20px)'],
                    ['offset' => 1, 'opacity' => 1, 'transform' => 'translateY(0)'],
                ],
                'options' => ['duration' => 600, 'easing' => 'ease-out'],
            ],
            'slide-down' => [
                'label' => 'Slide Down',
                'keyframes' => [
                    ['offset' => 0, 'opacity' => 0, 'transform' => 'translateY(-20px)'],
                    ['offset' => 1, 'opacity' => 1, 'transform' => 'translateY(0)'],
                ],
                'options' => ['duration' => 600, 'easing' => 'ease-out'],
            ],
            'slide-left' => [
                'label' => 'Slide Left',
                'keyframes' => [
                    ['offset' => 0, 'opacity' => 0, 'transform' => 'translateX(20px)'],
                    ['offset' => 1, 'opacity' => 1, 'transform' => 'translateX(0)'],
                ],
                'options' => ['duration' => 600, 'easing' => 'ease-out'],
            ],
            'slide-right' => [
                'label' => 'Slide Right',
                'keyframes' => [
                    ['offset' => 0, 'opacity' => 0, 'transform' => 'translateX(-20px)'],
                    ['offset' => 1, 'opacity' => 1, 'transform' => 'translateX(0)'],
                ],
                'options' => ['duration' => 600, 'easing' => 'ease-out'],
            ],
            'scale-in' => [
                'label' => 'Scale In',
                'keyframes' => [
                    ['offset' => 0, 'opacity' => 0, 'transform' => 'scale(0.9)'],
                    ['offset' => 1, 'opacity' => 1, 'transform' => 'scale(1)'],
                ],
                'options' => ['duration' => 400, 'easing' => 'ease-out'],
            ],
            'blur-in' => [
                'label' => 'Blur In',
                'keyframes' => [
                    ['offset' => 0, 'opacity' => 0, 'filter' => 'blur(10px)'],
                    ['offset' => 1, 'opacity' => 1, 'filter' => 'blur(0)'],
                ],
                'options' => ['duration' => 600, 'easing' => 'ease-out'],
            ],
        ];

        if ($this->hasEventHandlers(self::EVENT_REGISTER_ANIMATION_PRESETS)) {
            $event = new RegisterAnimationPresetsEvent(['presets' => $presets]);
            $this->trigger(self::EVENT_REGISTER_ANIMATION_PRESETS, $event);
            return $event->presets;
        }

        return $presets;
    }

    /**
     * Get available triggers
     */
    public function getTriggers(): array
    {
        $triggers = [];
        foreach (AnimationTrigger::cases() as $trigger) {
            $triggers[$trigger->value] = $trigger->label();
        }
        return $triggers;
    }

    /**
     * Build animation config for a node (used by the builder API)
     */
    public function buildAnimationConfig(string $preset, string $trigger, array $overrides = []): array
    {
        $presets = $this->getPresets();

        if (!isset($presets[$preset])) {
            return [];
        }

        $config = $presets[$preset];
        $config['trigger'] = $trigger;

        if (!empty($overrides['duration'])) {
            $config['options']['duration'] = (int) $overrides['duration'];
        }

        if (!empty($overrides['delay'])) {
            $config['options']['delay'] = (int) $overrides['delay'];
        }

        if (!empty($overrides['easing'])) {
            $config['options']['easing'] = $overrides['easing'];
        }

        return $config;
    }

    /**
     * Generate the frontend animation JS for a page
     */
    public function generateAnimationScript(): string
    {
        return <<<'JS'
/**
 * Rabbits Animation Engine
 * Uses CSS transitions + Web Animations API (WAAPI)
 */
(function() {
  'use strict';

  const RabbitsAnimate = {
    init() {
      this.observeScrollAnimations();
      this.bindClickAnimations();
      this.bindHoverAnimations();
      this.runLoadAnimations();
    },

    observeScrollAnimations() {
      const targets = document.querySelectorAll('[data-rabbits-animate="scroll-into-view"]');
      if (!targets.length) return;

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.animate(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1 });

      targets.forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
      });
    },

    bindClickAnimations() {
      document.querySelectorAll('[data-rabbits-animate="click"]').forEach(el => {
        el.addEventListener('click', () => this.animate(el));
      });
    },

    bindHoverAnimations() {
      document.querySelectorAll('[data-rabbits-animate="hover"]').forEach(el => {
        el.addEventListener('mouseenter', () => this.animate(el));
      });
    },

    runLoadAnimations() {
      document.querySelectorAll('[data-rabbits-animate="page-load"]').forEach(el => {
        el.style.opacity = '0';
        requestAnimationFrame(() => this.animate(el));
      });
    },

    animate(el) {
      const keyframesData = el.dataset.rabbitsKeyframes;
      const optionsData = el.dataset.rabbitsOptions;

      if (!keyframesData) return;

      try {
        const keyframes = JSON.parse(keyframesData);
        const options = optionsData ? JSON.parse(optionsData) : { duration: 600 };

        if (options.easing) {
          options.easing = options.easing;
        }

        el.animate(keyframes, options);

        // Set final state
        const lastFrame = keyframes[keyframes.length - 1];
        Object.entries(lastFrame).forEach(([key, value]) => {
          if (key !== 'offset') {
            el.style[key] = value;
          }
        });
      } catch (e) {
        console.warn('Rabbits animation error:', e);
      }
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => RabbitsAnimate.init());
  } else {
    RabbitsAnimate.init();
  }
})();
JS;
    }
}
