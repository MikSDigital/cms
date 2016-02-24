<?php

namespace Opifer\CmsBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Opifer\CmsBundle\Entity\Config;

class ConfigManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var Config[]
     */
    protected $configs;

    /**
     * @param EntityManager $em
     * @param string        $class
     */
    public function __construct(EntityManager $em, $class)
    {
        $this->em = $em;
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string      $name  Name of the config to update.
     * @param string|null $value New value for the config.
     *
     * @throws \RuntimeException If the config is not defined.
     */
    public function set($name, $value)
    {
        $config = $this->getRepository()->findOneBy(array('name' => $name));

        if ($config === null) {
            //throw $this->createNotFoundException($name);
        }

        $config->setValue($value);
        $this->em->flush($config);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (null === $this->configs) {
            $this->loadConfigs();
        }

        if (!array_key_exists($key, $this->configs)) {
            $availableKeys = implode(', ', array_keys($this->configs));
            new \RuntimeException(sprintf('Config "%s" could not be found. Use on of: %s', $key, $availableKeys));
        }

        return $this->configs[$key];
    }

    /**
     * @param string $key
     * @return Config
     */
    public function findOrCreate($key)
    {
        if ($this->exists($key)) {
            return $this->get($key);
        }

        $config = new $this->class();
        $config->setName($key);

        return $config;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        if (null === $this->configs) {
            $this->loadConfigs();
        }

        return array_key_exists($key, $this->configs);
    }

    public function keyValues()
    {
        if (null === $this->configs) {
            $this->loadConfigs();
        }

        $array = [];
        foreach ($this->configs as $key => $config) {
            $array[$key] = $config->getValue();
        }

        return $array;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->exists($key);
    }

    /**
     * Retrieve the configs and converts them for later use.
     */
    public function loadConfigs()
    {
        $configs = array();
        foreach ($this->getRepository()->findAll() as $config) {
            $configs[$config->getName()] = $config;
        }

        $this->configs = $configs;

        return $configs;
    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository($this->getClass());
    }
}
