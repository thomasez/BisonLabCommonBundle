<?php

namespace BisonLab\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait ContextBaseTrait
{
    /*
     * This is not for storage into the DB. So no, it should not have been here.
     */
    private $config;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $system
     *
     * @ORM\Column(name="system", type="string", length=255)
     */
    private $system;

    /**
     * @var string $object_name
     *
     * @ORM\Column(name="object_name", type="string", length=255)
     */
    private $object_name;

    /**
     * @var string $external_id
     *
     * @ORM\Column(name="external_id", type="string", length=80)
     */
    private $external_id;

    /**
     * @var string $url
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /* 
     * This could also be solved with keeping __construct() and then
     * use ContextBaseTrait { __construct as traitConstruct }
     * but I cannot see why it's better. To me it's more confusing.
     */
    public function traitConstruct($options = array())
    {
        if (isset($options['system'])) 
            $this->setSystem($options['system']);
        if (isset($options['object_name'])) 
            $this->setObjectName($options['object_name']);
        if (isset($options['external_id'])) 
            $this->setExternalId($options['external_id']);
        if (isset($options['url'])) 
            $this->setUrl($options['url']);
    }

    /* But for those using this trait fully, aka not having their own.: */
    public function __construct($options = array()) {
        return $this->traitConstruct($options);
    }

    /*
     * I'll have this one before all those setters and getters.
     * It's a default and if yyou do not want logging, copy this to your
     * context and return true.
     * Alas, default behaviour is to log context changes.
     */
    public function doNotLog()
    {
        return false;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set system
     *
     * @param string $system
     * @return $this
     */
    public function setSystem($system)
    {
        $this->system = $system;
        return $this;
    }

    /**
     * Get system
     *
     * @return string 
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Set object name
     *
     * @param string $object_name
     * @return a Context object
     */
    public function setObjectName($object_name)
    {
        $this->object_name = $object_name;
        return $this;
    }

    /**
     * Get object_name
     *
     * @return string 
     */
    public function getObjectName()
    {
        return $this->object_name;
    }

    /**
     * Set external_id
     *
     * @param string $externalId
     * @return $this
     */
    public function setExternalId($externalId)
    {
        $this->external_id = $externalId;
        return $this;
    }

    /**
     * Get external_id
     *
     * @return string 
     */
    public function getExternalId()
    {
        return $this->external_id;
    }

    /**
     * Reset url
     * Aka use the config and template to create a new URL.
     *
     * @param string $url
     * @return $this
     */
    public function resetUrl()
    {
        // Good old one.
        if (isset($this->config['url_base'])) {
            $this->url = $this->config['url_base'] . $this->getExternalId();
        }    
        // Or we have a twig template'ish. (Notice that it will override the
        // url_base one.
        if (isset($this->config['url_template'])) {
            $this->url = $this->config['url_template'];
            $context_arr = array(
                'external_id' => $this->getExternalId(),
                'system' => $this->getSystem(),
                'object_name' => $this->getObjectName(),
                'owner_id' => $this->getOwnerId(),
                );
            foreach ($context_arr as $key => $val) {
                $this->url = preg_replace('/\{\{\s?'.$key.'\s?\}\}/i',
                                $val , $this->url);
            }
        }    
        return $this;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return a Context object
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        /*
         * May be overkill, but if the config has changed it may be useful to
         * try again. 
         */
        if (empty($this->url))
            $this->resetUrl();
        return $this->url;
    }

    public function __toString()
    {
        return (string)$this->id;
    }

    public function setConfig($config = array())
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->getConfig()['label'];
    }

    /**
     * Get Context type
     *
     * @return string 
     */
    public function getContextType()
    {
        return $this->getConfig()['type'];
    }

    /**
     * Get Default (Not that I have any idea if we need this or not..)
     *
     * @return boolean 
     */
    public function getDefault()
    {
        return $this->getConfig()['default'];
    }

    /*
     * This is decided by the context type and the existance of an external id.
     * It should be no context object if there are no external ID but that's
     * another story.
     */
    public function isDeleteable()
    {
        // It's not really working well as it is now. Not bad enough so we'll
        // keep it but if you end up having issues, just clone the function
        // into your cojntext object and return true.
        return (
            ($this->getConfig()['type'] == 'external_master' || 
             $this->getConfig()['type'] == 'master') 
            && $this->getExternalId()) ? false : true;
    }

    /**
     * Generic main object setting.
     *
     * @return object
     */
    public function setOwner($object)
    {
        $this->owner = $object;
    }

    /**
     * Generic main object.
     *
     * @return object
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /*
     * Owner helpers.
     */
    public function getOwnerId()
    {
        return $this->getOwner()->getid();
    }
}
