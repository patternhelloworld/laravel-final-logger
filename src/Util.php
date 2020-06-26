<?php


namespace Arwg\FinalLogger;


trait Util
{


    private function get_client_ip()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }


    private function get_property($propertyPath, $object)
    {
        foreach ($propertyPath as $propertyName) {

            if (is_object($object)) {

                if (!property_exists($object, $propertyName)) return NULL;

                $property = $object->{$propertyName};

            } else if (is_array($object)) {

                if(!$object[$propertyName]) return NULL;

                $property = $object[$propertyName];

            } else {

                return NULL;
            }

            if (!is_object($property) && !is_array($property)) {
                return $property;
            }

            $object = $property;

        }
        return $object;
    }

    private function set_property($propertyPath, &$object, $value)
    {

        $lastProperty = array_pop($propertyPath);
        // get the object to which the last property should belong
        $targetObject = $this->get_property($propertyPath, $object);
        IF(!$targetObject){
            return;
        }
        // and set it to value if it is valid
        if (is_object($targetObject) && property_exists($targetObject, $lastProperty)) {
            $targetObject->{$lastProperty} = $value;
        } else if (isset($targetObject) && is_array($targetObject)) {
            //$newTargetObject = (object) $targetObject;
            $targetObject[$lastProperty] = $value;
        }
    }


}
