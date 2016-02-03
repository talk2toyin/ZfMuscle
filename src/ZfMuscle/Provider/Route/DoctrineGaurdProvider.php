<?php

namespace ZfMuscle\Provider\Route;

use Doctrine\Common\Persistence\ObjectRepository;
use BjyAuthorize\Provider\Rule\ProviderInterface;

/**
 * Guard provider based on a {@see \Doctrine\Common\Persistence\ObjectRepository}
 */
class DoctrineGaurdProvider implements ProviderInterface
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository
     */
    protected $objectRepository;

    /**
     * @var \Doctrine\ORM\EntityManaager
     */
    protected $objectManager;
    
    /**
     * Set default controller gaurd rules
     * @var type
     */
    protected $defaultRules;

    /**
     * @param \Doctrine\Common\Persistence\ObjectRepository $objectRepository
     */
    public function __construct(ObjectRepository $objectRepository, $objectManager, $default=null) {
        $this->objectRepository = $objectRepository;
        $this->objectManager = $objectManager;
        $this->defaultRules = !is_null($default) ? $default : '';
    }

    /**
     * Here we read rules from DB and put them into a form that BjyAuthorize's Controller.php understands
     */
    public function getRules()
    {
        $rules = array(); // initialize the rules array
        // get the doctrine shemaManager
        $schemaManager = $this->objectManager->getConnection()->getSchemaManager();
        
        // check if the roles table exists, if it does not, do not bother querying
        if ($schemaManager->tablesExist(array('roles')) === true)
        {
            //read from object store a set of (role, controller, action)
            $result = $this->objectRepository->findAll();
            
            // if a result set exists
            if (count($result))
            {
                //transform to object BjyAuthorize will understand
                foreach ($result as $role)
                {
                    $roleId = $role->getRoleId();

                    // check if any resource access has been defined before
                    // if it has, continue with normal operations
                    // else, allow access to this first user
                    if (!$role->getResources())
                    {
                        continue;
                    }
                    
                    foreach ($role->getResources() as $rle) {
                        $resourceId = strtolower($rle->getResourceId());

                        $rules[$resourceId]['route'] = $resourceId;
                        $rules[$resourceId]['roles'][] = $roleId;
                    }
                }
                
                // merge with default rules
                if (!empty($this->defaultRules))
                {
                    foreach ($this->defaultRules as $default)
                    {
                        $route = $default['route'];
                        $rules[$route] = $default;
                    }
                }
            }
        }
        return $rules;
    }

}
