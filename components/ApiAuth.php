<?php

namespace app\components;

interface ApiAuth
{
    public function actionLogin();
    public function actionLogout();
    public function actionRegister();
}
