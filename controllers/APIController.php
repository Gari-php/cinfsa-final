<?php

namespace Controllers;

use Models\Persona;
class APIController{
    public static function index(){
        $personas = Persona::all();

        debuguear($personas);
    }

}