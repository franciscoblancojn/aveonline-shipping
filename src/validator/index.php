<?php

class AVSHME_Validator_Class  {
    private ?string $name = null;
    private $data;
    private array $rules = [];
    private array $messages = [];

    public function __construct(?string $name = null) {
        $this->name = $name;
    }

    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    public function setData($data): self {
        $this->data = $data;
        return $this;
    }

    public function isRequired(string $message = "Este campo es obligatorio"): self {
        $this->rules['required'] = true;
        $this->messages['required'] = $message;
        return $this;
    }

    public function isString(string $message = "Debe ser una cadena de texto"): self {
        $this->rules['string'] = true;
        $this->messages['string'] = $message;
        return $this;
    }

    public function isNumber(string $message = "Debe ser un número"): self {
        $this->rules['number'] = true;
        $this->messages['number'] = $message;
        return $this;
    }

    public function isBoolean(string $message = "Debe ser un valor booleano"): self {
        $this->rules['boolean'] = true;
        $this->messages['boolean'] = $message;
        return $this;
    }

    public function isArray($arraySchema,string $message = "Debe ser un array"): self {
        $this->rules['array'] = true;
        $this->arraySchema = $arraySchema;
        $this->messages['array'] = $message;
        return $this;
    }

    public function isObject($objectSchema, string $message = "Debe ser un objeto"): self {
        $this->rules['object'] = true;
        $this->objectSchema = $objectSchema;
        $this->messages['object'] = $message;
        return $this;
    }

    public function isDate(string $message = "Debe ser una fecha válida"): self {
        $this->rules['date'] = true;
        $this->messages['date'] = $message;
        return $this;
    }

    public function isEmail(string $message = "Debe ser un correo electrónico válido"): self {
        $this->rules['email'] = true;
        $this->messages['email'] = $message;
        return $this;
    }

    public function isMin(int $min, string $message ): self {
        $this->rules['min'] = $min;
        $this->messages['min'] = $message ||  "Debe ser mayor que $min";
        return $this;
    }

    public function isMax(int $max, string $message ): self {
        $this->rules['max'] = $max;
        $this->messages['max'] = $message || "Debe ser menor que $max";
        return $this;
    }

    public function isEqual($value, string $message ): self {
        $this->rules['equal'] = $value;
        $this->messages['equal'] = $message || "Debe ser igual a $value";
        return $this;
    }

    public function isLength(int $length, string $message ): self {
        $this->rules['length'] = $length;
        $this->messages['length'] = $message || "Debe tener una longitud de $length caracteres";
        return $this;
    }

    public function isRegex(string $pattern, string $message = "Formato inválido"): self {
        $this->rules['regex'] = $pattern;
        $this->messages['regex'] = $message;
        return $this;
    }

    public function isEnum(array $enumValues, string $message = "Valor no permitido"): self {
        $this->rules['enum'] = $enumValues;
        $this->messages['enum'] = $message;
        return $this;
    }

    public function validate($data) {
        try {
            $this->data = $data;
            $this->onRequired();
            $this->onString();
            $this->onNumber();
            $this->onBoolean();
            $this->onArray();
            $this->onObject();
            $this->onDate();
            $this->onEmail();
            $this->onMin();
            $this->onMax();
            $this->onEqual();
            $this->onLength();
            $this->onRegex();
            $this->onEnum();

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function onRequired() {
        if (isset($this->rules['required']) && empty($this->data)) {
            throw new Exception($this->messages['required']);
        }
    }

    private function onString() {
        if (isset($this->rules['string']) && !is_string($this->data)) {
            throw new Exception($this->messages['string']);
        }
    }

    private function onNumber() {
        if (isset($this->rules['number']) && !is_numeric($this->data)) {
            throw new Exception($this->messages['number']);
        }
    }

    private function onBoolean() {
        if (isset($this->rules['boolean']) && !is_bool($this->data)) {
            throw new Exception($this->messages['boolean']);
        }
    }

    private function onDate() {
        if (isset($this->rules['date']) && !strtotime($this->data)) {
            throw new Exception($this->messages['date']);
        }
    }

    private function onEmail() {
        if (isset($this->rules['email']) && !filter_var($this->data, FILTER_VALIDATE_EMAIL)) {
            throw new Exception($this->messages['email']);
        }
    }

    private function onMin() {
        if (isset($this->rules['min']) && is_numeric($this->data) && $this->data < $this->rules['min']) {
            throw new Exception($this->messages['min']);
        }
    }

    private function onMax() {
        if (isset($this->rules['max']) && is_numeric($this->data) && $this->data > $this->rules['max']) {
            throw new Exception($this->messages['max']);
        }
    }

    private function onEqual() {
        if (isset($this->rules['equal']) && $this->data != $this->rules['equal']) {
            throw new Exception($this->messages['equal']);
        }
    }

    private function onLength() {
        if (isset($this->rules['length']) && is_string($this->data) && strlen($this->data) != $this->rules['length']) {
            throw new Exception($this->messages['length']);
        }
    }

    private function onRegex() {
        if (isset($this->rules['regex']) && !preg_match($this->rules['regex'], $this->data)) {
            throw new Exception($this->messages['regex']);
        }
    }

    private function onEnum() {
        if (isset($this->rules['enum']) && !in_array($this->data, $this->rules['enum'])) {
            throw new Exception($this->messages['enum']);
        }
    }

    private function onObject() {
        if (!isset($this->rules['object'])) {
            return;
        }
        if (!is_array($this->data) && !is_object($this->data)) {
            throw new Exception($this->messages['object']);
        }

        if ($this->objectSchema) {
            foreach ($this->objectSchema as $key => $validator) {
                AVSHME_addLogAveonline(array(
                    "type"=>"validator",
                    "key"=>$key,
                    "validator"=>$validator,
                    "data"=>$this->data,
                ));
                if ( isset($this->data[$key])) {
                    $value =  $this->data[$key];
                    $validator->validate($value);
                } else {
                    $validator->validate(null);
                }
            }
        }
    }

    private function onArray() {
        if (!isset($this->rules['array'])) {
            return;
        }
        if (!is_array($this->data)) {
            throw new Exception($this->messages['array']);
        }

        if ($this->arraySchema) {
            foreach ($this->data as $index => $value) {
                $this->arraySchema->validate($value);
            }
        }
    }
}

function AVSHME_Validator(?string $name = null) {
   return new AVSHME_Validator_Class($name); 
}