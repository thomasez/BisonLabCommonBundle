<?php

namespace BisonLab\CommonBundle\Entity;

/*
 * This is the basic attributes stuff.
 * If you have to, override these functions in the Entity you need to do it.
 * 
 * Wonder why I use $this->getAttribute instead of $this->attributes?
 * Reason is that the Entity using this trait can then have it's own
 * getAtttributes overriding this one.
 *
 * Like a persons attributes merges with the groups the person is a
 * member of. Or a child (entity) inheriting the parents attributes.
 * 
 * And yes, it makes setting attributes kinda messy.
 */

trait AttributesTrait
{
    /**
     * @var json $attributes
     *
     * @ORM\Column(name="attributes", type="json", nullable=true)
     * @Gedmo\Versioned
     */
    private $attributes = [];

    /**
     * Set attribute
     *
     * @return $this
     */
    public function setAttribute($Attribute, $value)
    {
        // Gotta lowercase it all.
        $attribute = strtolower($Attribute);
        // Speed king!
        $attributes = $this->getAttributes();
        if (null === $value) {
            unset($attributes[$attribute]);
        } else {
            $attributes[$attribute] = $value;
        }
        $this->setAttributes($attributes);
        return $this;
    }

    /**
     * Get attribute
     *
     * @return mixed 
     */
    public function getAttribute($Attribute)
    {
        $attributes = $this->getAttributes();
        return $attributes[$Attribute] ?? null;
    }

    /**
     * Set attributes
     *
     * @param array $Attributes
     * @return $this
     */
    public function setAttributes(array $Attributes)
    {
        $this->attributes = $Attributes;
        return $this;
    }

    /**
     * Get attributes
     *
     * @return array 
     */
    public function getAttributes()
    {
        return $this->attributes ?: [];
    }

    /**
     * Get attributes as an alias. (Simpler to use in templates. object.a.key)
     *
     * @return array 
     */
    public function getA($key = null)
    {
        return $this->getAttribute($key);
    }
}
