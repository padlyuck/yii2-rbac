<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace dektrium\rbac\models;

use dektrium\rbac\components\DbManager;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\rbac\Item;

/**
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
abstract class AuthItem extends Model
{
    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var string */
    public $rule;

    /** @var string[] */
    public $children = [];

    /** @var \yii\rbac\Item */
    public $item;

    /** @var \dektrium\rbac\components\DbManager */
    protected $manager;

    /** @inheritdoc */
    public function init()
    {
        parent::init();
        $this->manager = \Yii::$app->authManager;
        if ($this->item instanceof Item) {
            $this->name        = $this->item->name;
            $this->description = $this->item->description;
            $this->children    = array_keys($this->manager->getChildren($this->item->name));
        }
    }

    /** @inheritdoc */
    public function scenarios()
    {
        return [
            'create' => ['name', 'description', 'children', 'rule'],
            'update' => ['name', 'description', 'children', 'rule'],
        ];
    }

    /** @inheritdoc */
    public function rules()
    {
        return [
            ['name', 'required'],
            [['name', 'rule'], 'match', 'pattern' => '/^[\w-]+$/'],
            [['name', 'description', 'rule'], 'trim'],
            ['name', function () {
                if ($this->manager->getItem($this->name) !== null) {
                    $this->addError('name', \Yii::t('rbac', 'Auth item with such name already exists'));
                }
            }, 'when' => function () {
                return $this->scenario == 'create' || $this->item->name != $this->name;
            }],
            ['rule', function () {
                if ($this->manager->getRule($this->rule) === null) {
                    $this->addError('rule', \Yii::t('rbac', 'There is no rule with such name'));
                }
            }],
            ['children', function () {
                foreach ($this->children as $child) {
                    if ($this->manager->getItem($child) == null) {
                        $this->addError('children', \Yii::t('rbac', 'There is neither role nor permission with name "' . $child .  '"'));
                    }
                }
            }]
        ];
    }

    /**
     * Saves item.
     *
     * @return bool
     */
    public function save()
    {
        if ($this->validate() == false) {
            return false;
        }

        if ($isNewItem = ($this->item === null)) {
            $this->item = $this->createItem($this->name);
        } else {
            $oldName = $this->item->name;
        }

        $this->item->name        = $this->name;
        $this->item->description = $this->description;

        // TODO: add rules assignment

        if ($isNewItem) {
            \Yii::$app->session->setFlash('success', \Yii::t('rbac', 'Item has been created'));
            $this->manager->add($this->item);
        } else {
            \Yii::$app->session->setFlash('success', \Yii::t('rbac', 'Item has been updated'));
            $this->manager->update($oldName, $this->item);
        }

        if (is_array($this->children)) {
            foreach ($this->children as $name) {
                $child = $this->manager->getItem($name);
                if ($this->manager->hasChild($this->item, $child) == false) {
                    $this->manager->addChild($this->item, $child);
                }
            }
        }

        return true;
    }

    /**
     * @return array An array of unassigned items.
     */
    abstract public function getUnassignedItems();

    /**
     * @param  string         $name
     * @return \yii\rbac\Item
     */
    abstract protected function createItem($name);
}