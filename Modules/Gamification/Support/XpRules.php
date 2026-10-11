<?php

namespace Modules\Gamification\Support;

use Modules\Gamification\Contracts\XpRule;

class XpRules
{
    /** @var array<string, XpRule> */
    private array $rules = [];

    public function add(XpRule ...$rules): void
    {
        foreach ($rules as $rule) {
            $this->rules[$rule->key()] = $rule;
        }
    }

    /** @return array<string, XpRule> */
    public function all(): array
    {
        return $this->rules;
    }

    public function get(string $key): ?XpRule
    {
        return $this->rules[$key] ?? null;
    }
}
