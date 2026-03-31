<?php 
class Validator
{
    private $errors = array();
    private $data   = array();

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Campo obrigatório
     */
    public function required($field, $label = null)
    {
        $label = $label ?: $field;
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "O campo '{$label}' é obrigatório.";
        }
        return $this;
    }

    /**
     * Tamanho mínimo de string
     */
    public function minLength($field, $min, $label = null)
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "O campo '{$label}' deve ter pelo menos {$min} caracteres.";
        }
        return $this;
    }

    /**
     * Tamanho máximo de string
     */
    public function maxLength($field, $max, $label = null)
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = "O campo '{$label}' deve ter no máximo {$max} caracteres.";
        }
        return $this;
    }

    /**
     * Valor deve estar em uma lista permitida
     */
    public function in($field, $allowed, $label = null)
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed)) {
            $this->errors[$field] = "O campo '{$label}' contém um valor inválido.";
        }
        return $this;
    }

    /**
     * Campo deve ser array não vazio
     */
    public function arrayNotEmpty($field, $label = null)
    {
        $label = $label ?: $field;
        if (!isset($this->data[$field]) || !is_array($this->data[$field]) || empty($this->data[$field])) {
            $this->errors[$field] = "O campo '{$label}' deve ser um array não vazio.";
        }
        return $this;
    }

    public function fails()
    {
        return !empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }

    public function get($field, $default = null)
    {
        return isset($this->data[$field]) ? $this->data[$field] : $default;
    }
}
?>