<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation;

/**
 * Represents an Accept-* header.
 *
 * An accept header is compound with a list of items,
 * sorted by descending quality.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 */
class AcceptHeader
{
    const EPSILON = 0.00001;

    /**
     * @var AcceptHeaderItem[]
     */
    private $items;

    /**
     * @var bool
     */
    private $sorted;

    /**
     * Builds an AcceptHeader instance from a string.
     *
     * @param string $headerValue
     *
     * @return AcceptHeader
     */
    public static function fromString($headerValue)
    {
        return new static(array_map(function ($itemValue) {
            return AcceptHeaderItem::fromString($itemValue);
        }, preg_split('/\s*(?:,*("[^"]+"),*|,*(\'[^\']+\'),*|,+)\s*/', $headerValue, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)));
    }

    /**
     * Constructor.
     *
     * @param array $items
     */
    public function __construct(array $items)
    {
        $this->sorted = true;
        $this->items = array();
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Returns header  value's string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return implode(',', $this->items);
    }

    /**
     * Tests if header has given value.
     *
     * @param string $value
     *
     * @return bool
     */
    public function has($value)
    {
        return isset($this->items[$value]);
    }

    /**
     * Returns given value's item, if exists.
     *
     * @param string $value
     *
     * @return AcceptHeaderItem|null
     */
    public function get($value)
    {
        return isset($this->items[$value]) ? $this->items[$value] : null;
    }

    /**
     * Adds an item.
     *
     * @param AcceptHeaderItem $item
     *
     * @return AcceptHeader
     */
    public function add(AcceptHeaderItem $item)
    {
        $this->items[$item->getValue()] = $item;
        $this->sorted = false;

        return $this;
    }

    /**
     * Returns all items.
     *
     * @return AcceptHeaderItem[]
     */
    public function all()
    {
        $this->ensureSorted();

        return $this->items;
    }

    /**
     * Filters items on their value using given regex.
     *
     * @param string $pattern
     *
     * @return AcceptHeader
     */
    public function filter($pattern)
    {
        return new static(array_filter($this->items, function (AcceptHeaderItem $item) use ($pattern) {
            return preg_match($pattern, $item->getValue());
        }));
    }

    /**
     * Returns first item.
     *
     * @return AcceptHeaderItem|null
     */
    public function first()
    {
        $this->ensureSorted();

        return count($this->items) ? current($this->items) : null;
    }

    /**
     * Ensures items are sorted by descending quality
     */
    private function ensureSorted()
    {
        if (!$this->sorted) {
            $epsilon = self::EPSILON;
            uasort($this->items, function (AcceptHeaderItem $a, AcceptHeaderItem $b) use ($epsilon) {
                return $a->getQuality() - $epsilon < $b->getQuality() + $epsilon;
            });
        }
    }
}