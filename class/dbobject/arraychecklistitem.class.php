<?php
namespace dbObject;

class ArrayChecklistItem extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ChecklistItem';
    }

    public function loadForChecklist($checklistId, $activeOnly = true)
    {
        $this->exchangeArray([]);
        $checklistId = (int)$checklistId;
        if ($checklistId <= 0) {
            return;
        }
        $where = [['field' => 'IDchecklist', 'value' => $checklistId]];
        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }
        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'position', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
        $this->sortForExecution();
    }

    public function sortForExecution()
    {
        $items = [];
        $itemsById = [];
        $fallbackOrder = [];
        foreach ($this as $item) {
            if (!($item instanceof ChecklistItem)) {
                continue;
            }
            $itemId = (int)$item->getId();
            if ($itemId <= 0) {
                continue;
            }
            $fallbackOrder[$itemId] = count($items);
            $items[$itemId] = $item;
            $itemsById[] = $itemId;
        }
        if (count($items) < 2) {
            return;
        }

        $dependencies = new ArrayChecklistItemDependency();
        $dependencies->loadForItems($itemsById);
        $requirements = [];
        $children = [];
        $indegree = array_fill_keys($itemsById, 0);
        foreach ($dependencies as $dependency) {
            if (!($dependency instanceof ChecklistItemDependency)) {
                continue;
            }
            $itemId = (int)$dependency->get('IDchecklistitem');
            $requiredItemId = (int)$dependency->get('IDchecklistitem_required');
            if (!isset($items[$itemId]) || !isset($items[$requiredItemId]) || $itemId === $requiredItemId) {
                continue;
            }
            $requirements[$itemId][] = [
                'itemId' => $requiredItemId,
                'delay' => self::delaySortValue($dependency->get('delay_value'), $dependency->get('delay_unit')),
            ];
            if (!isset($children[$requiredItemId][$itemId])) {
                $children[$requiredItemId][$itemId] = true;
                $indegree[$itemId]++;
            }
        }

        $offsetCache = [];
        $resolving = [];
        $resolveOffset = function ($itemId) use (&$resolveOffset, &$offsetCache, &$resolving, $items, $requirements) {
            if (isset($offsetCache[$itemId])) {
                return $offsetCache[$itemId];
            }
            if (isset($resolving[$itemId])) {
                return PHP_INT_MAX;
            }
            $resolving[$itemId] = true;
            $item = $items[$itemId];
            $activationType = ChecklistItem::normalizeActivationType($item->get('activation_type'));
            if ($activationType === ChecklistItem::ACTIVATION_MANUAL) {
                $offset = PHP_INT_MAX;
            } elseif ($activationType === ChecklistItem::ACTIVATION_AFTER_START) {
                $offset = self::delaySortValue($item->get('delay_value'), $item->get('delay_unit'));
            } elseif ($activationType === ChecklistItem::ACTIVATION_AFTER_COMPLETION) {
                $offset = PHP_INT_MAX;
                if (!empty($requirements[$itemId])) {
                    $offset = 0;
                    foreach ($requirements[$itemId] as $requirement) {
                        $requiredOffset = $resolveOffset((int)$requirement['itemId']);
                        if ($requiredOffset === PHP_INT_MAX) {
                            $offset = PHP_INT_MAX;
                            break;
                        }
                        $offset = max($offset, $requiredOffset + (int)$requirement['delay']);
                    }
                }
            } else {
                $offset = 0;
            }
            unset($resolving[$itemId]);
            $offsetCache[$itemId] = $offset;
            return $offset;
        };
        foreach ($itemsById as $itemId) {
            $resolveOffset($itemId);
        }

        $activationRank = static function (ChecklistItem $item) {
            $activationType = ChecklistItem::normalizeActivationType($item->get('activation_type'));
            if ($activationType === ChecklistItem::ACTIVATION_IMMEDIATE) {
                return 0;
            }
            if ($activationType === ChecklistItem::ACTIVATION_AFTER_START) {
                return 1;
            }
            if ($activationType === ChecklistItem::ACTIVATION_AFTER_COMPLETION) {
                return 2;
            }
            return 3;
        };
        $sortIds = static function (&$itemIds) use ($items, $offsetCache, $fallbackOrder, $activationRank) {
            usort($itemIds, static function ($leftId, $rightId) use ($items, $offsetCache, $fallbackOrder, $activationRank) {
                $comparison = ($offsetCache[$leftId] ?? PHP_INT_MAX) <=> ($offsetCache[$rightId] ?? PHP_INT_MAX);
                if ($comparison !== 0) {
                    return $comparison;
                }
                $comparison = $activationRank($items[$leftId]) <=> $activationRank($items[$rightId]);
                if ($comparison !== 0) {
                    return $comparison;
                }
                $comparison = ($fallbackOrder[$leftId] ?? PHP_INT_MAX) <=> ($fallbackOrder[$rightId] ?? PHP_INT_MAX);
                return $comparison !== 0 ? $comparison : ($leftId <=> $rightId);
            });
        };

        $available = [];
        foreach ($itemsById as $itemId) {
            if (($indegree[$itemId] ?? 0) === 0) {
                $available[] = $itemId;
            }
        }
        $orderedIds = [];
        while (count($available) > 0) {
            $sortIds($available);
            $itemId = array_shift($available);
            $orderedIds[] = $itemId;
            foreach (array_keys($children[$itemId] ?? []) as $childId) {
                $indegree[$childId]--;
                if ($indegree[$childId] === 0) {
                    $available[] = $childId;
                }
            }
        }

        if (count($orderedIds) < count($itemsById)) {
            $remainingIds = array_values(array_diff($itemsById, $orderedIds));
            $sortIds($remainingIds);
            $orderedIds = array_merge($orderedIds, $remainingIds);
        }
        $this->exchangeArray(array_map(static function ($itemId) use ($items) {
            return $items[$itemId];
        }, $orderedIds));
    }

    private static function delaySortValue($value, $unit)
    {
        $value = (int)$value;
        $unit = ChecklistItem::normalizeDelayUnit($unit) ?: ChecklistItem::DELAY_DAY;
        if ($unit === ChecklistItem::DELAY_HOUR) {
            return $value * 3600;
        }
        if ($unit === ChecklistItem::DELAY_WEEK) {
            return $value * 604800;
        }
        if ($unit === ChecklistItem::DELAY_MONTH) {
            return $value * 2629800;
        }
        return $value * 86400;
    }
}
?>
