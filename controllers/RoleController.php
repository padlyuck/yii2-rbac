<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace dektrium\rbac\controllers;

use yii\data\ArrayDataProvider;
use yii\rbac\Role;
use yii\web\NotFoundHttpException;

/**
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class RoleController extends ItemControllerAbstract
{
    /** @var string */
    protected $modelClass = 'dektrium\rbac\models\Role';

    /** @inheritdoc */
    protected function getDataProvider()
    {
        return \Yii::createObject([
            'class'     => ArrayDataProvider::className(),
            'allModels' => \Yii::$app->authManager->getRoles(),
        ]);
    }

    /** @inheritdoc */
    protected function getItem($name)
    {
        $role = \Yii::$app->authManager->getRole($name);

        if ($role instanceof Role) {
            return $role;
        }

        throw new NotFoundHttpException;
    }
}