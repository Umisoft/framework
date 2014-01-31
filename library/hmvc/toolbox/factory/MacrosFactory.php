<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\hmvc\toolbox\factory;

use umi\hmvc\component\IComponent;
use umi\hmvc\exception\OutOfBoundsException;
use umi\hmvc\exception\RuntimeException;
use umi\hmvc\macros\IMacros;
use umi\hmvc\macros\IMacrosFactory;
use umi\hmvc\model\IModelAware;
use umi\hmvc\model\IModelFactory;
use umi\toolkit\factory\IFactory;
use umi\toolkit\factory\TFactory;
use umi\toolkit\prototype\IPrototype;

/**
 * Фабрика макросов для компонента.
 */
class MacrosFactory implements IMacrosFactory, IFactory, IModelAware
{

    use TFactory;

    /**
     * @var array $macrosList список макросов компонента
     */
    protected $macrosList = [];
    /**
     * @var IModelFactory $modelFactory фабрика моделей
     */
    protected $modelFactory;
    /**
     * @var IComponent $component компонент
     */
    protected $component;

    /**
     * Конструктор.
     * @param IComponent $component
     * @param array $macrosList список макросов в формате ['macrosName' => 'macrosClassName', ...]
     */
    public function __construct(IComponent $component, array $macrosList)
    {
        $this->component = $component;
        $this->macrosList = $macrosList;
    }

    /**
     * {@inheritdoc}
     */
    public function createMacros($name, $params = [])
    {
        if (!$this->hasMacros($name)) {
            throw new OutOfBoundsException($this->translate(
                'Cannot create "{name}" macros. Macros is not registered in component "{component}".',
                [
                    'name' => $name,
                    'component' => $this->component->getPath()
                ]
            ));
        }

        return $this->createMacrosByClass($this->macrosList[$name], $params);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMacros($name)
    {
        return isset($this->macrosList[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function setModelFactory(IModelFactory $factory)
    {
        $this->modelFactory = $factory;
    }

    /**
     * Создает макрос заданного класса.
     * @param string $class класс макроса
     * @param array $params параметры вызова макроса
     * @throws RuntimeException если макрос не callable
     * @return IMacros
     */
    protected function createMacrosByClass($class, $params = [])
    {
        $macros = $this->getPrototype(
            $class,
            ['umi\hmvc\macros\IMacros'],
            function (IPrototype $prototype) use ($class)
            {
                /** @noinspection PhpParamsInspection */
                if (!is_callable($prototype->getPrototypeInstance())) {
                    throw new RuntimeException(
                        $this->translate(
                            'Macros "{class}" should be callable.',
                            ['class' => $class]
                        )
                    );
                }
                $prototype->registerConstructorDependency(
                    'umi\hmvc\model\IModel',
                    function ($concreteClassName) {
                        if ($this->modelFactory) {
                            return $this->modelFactory->createByClass($concreteClassName);
                        }

                        return null;
                    }
                );
            }
        )
            ->createInstance([], $params);

        if ($macros instanceof IModelAware) {
            $macros->setModelFactory($this->modelFactory);
        }

        return $macros;
    }
}
 