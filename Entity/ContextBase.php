<?php

namespace RedpillLinpro\CommonBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\MappedSuperclass */
class ContextBase
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
     * @Gedmo\Versioned
     */
    private $system;

    /**
     * @var string $object_name
     *
     * @ORM\Column(name="object_name", type="string", length=255)
     * @Gedmo\Versioned
     */
    private $object_name;

    /**
     * @var string $external_id
     *
     * @ORM\Column(name="external_id", type="string", length=80)
     * @Gedmo\Versioned
     */
    private $external_id;

    /**
     * @var string $url
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $url;

    public function __construct($options = array()) {

        if (isset($options['system'])) 
            $this->setSystem($options['system']);
        if (isset($options['object_name'])) 
            $this->setObjectName($options['object_name']);
        if (isset($options['external_id'])) 
            $this->setExternalId($options['external_id']);
        if (isset($options['url'])) 
            $this->setUrl($options['url']);
        if (isset($options['line'])) 
            $this->setLine($options['line']);

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
     * @return LineContext
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
     * @return LineContext
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
     * @return LineContext
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

}
