<?php

abstract class Field_Calculated extends Field {
    abstract function getValue($model, $data);
}