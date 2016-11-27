<?php

namespace TomLegkov\EncryptableFields;

use TomLegkov\EncryptableFields\Exceptions\EncryptedFileNotFoundException;
use TomLegkov\EncryptableFields\Exceptions\KeyNotEncryptedException;
use Crypt;

trait Encryptable {

    /**
     * @var bool $_returnRaw    Return the encrypted file name instead of the decryption?
     */
    private $_returnRaw = false;

    /**
     * Generates a path to the encryption file
     * @param string $file      Name of the encryption file
     * @return string 
     */
    private function pathToEncryption($file){
        return  config('encryptable-fields.storage_path') .
                DIRECTORY_SEPARATOR . 
                $file . 
                '.' . 
                config('encryptable-fields.extension');   
    }

    /**
     * Determines where or not the encryption file exists
     * @param string $file      Name of the encryption file
     * @return bool
     */
    private function encryptionExists($file){   
        return file_exists($this->pathToEncryption($file));
    }

    /**
     * Creates a new encryption file name
     * @return string
     */
    private function createName(){
        do {
            $name = str_random(config('encryptable-fields.file_name'));
        } while ($this->encryptionExists($name));
        return $name;
    }

    /**
     * Creates a new encryption file
     * @param string $name      Name of the encryption file
     * @param string $value     The encrypted data
     * @return void
     */
    private function createEncryption($name, $value){
        $folder = config('encryptable-fields.storage_path');
        if (!file_exists($folder)) {
            mkdir($folder);
        }

        $file = $this->pathToEncryption($name);
        file_put_contents($file, $value);
    }

    /**
     * A scope to search for encrypted values in the database
     * @param QueryBuilder $query       The QueryBuilder
     * @param string $key               The column name
     * @param string $value             The non-encrypted value to search for
     * @return void
     */
    public function scopeWhereEncrypted($query, $key, $value){
        if (!isset($this->encryptable) || !in_array($key, $this->encryptable)){
            throw new KeyNotEncryptedException("$key is not encryptable");
        }
 
        $all = $this->get(['id', $key]);
        $ids = [];

        foreach ($all as $file){
            $decrypted = $file->$key;
            $id = $file->id;
            if ($decrypted === $value){
                $ids[] = $id;
            }
        }

        $query->whereIn('id', $ids);
    }

    /**
     * Gets an attribute
     * If the attribute was encrypted and is inside the $this->encryptable var, it will decrypt it
     * If $this->_returnRaw is true, the value will be returned without the decryption process
     * $this->_returnRaw is set to false after execution no matter what
     * @param string $key   The attribute key
     * @return mixed
     */
    public function getAttribute($key) {
        $value = parent::getAttribute($key);

        if (!$this->_returnRaw && isset($this->encryptable) && in_array($key, $this->encryptable)) {
            
            try {
                $contents = file_get_contents($this->pathToEncryption($value));
            } catch (\Exception $e) {
                throw new EncryptedFileNotFoundException("Couldn't find key $key with value $value");
            }
            $decrypted = Crypt::decrypt($contents);
            $value = $decrypted;
        }

        $this->_returnRaw = false;
        return $value;
    }

    /**
     * Sets an attribute
     * If the attribute is inside the $this->encryptable var, it will be encrypted
     * @param string $key   The attribute key
     * @param mixed $value  The attribute value
     * @return mixed
     */
    public function setAttribute($key, $value) {

        if (isset($this->encryptable) && in_array($key, $this->encryptable)) {
            # Delete old
            $this->_returnRaw = true;
            $old = $this->{$key};
            if (strlen($old) === config('encryptable-fields.file_name')) {
                unlink($this->pathToEncryption($old));
            }

            # Create new
            $encrypted  = Crypt::encrypt($value);
            $name = $this->createName();
            $this->createEncryption($name, $encrypted);
            $value = $name; // store the name
        }

        return parent::setAttribute($key, $value);
    }

}