<?php

namespace App\Rules;

use Closure;
// use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Rule;
class AtLeastOneChecked implements Rule
// class AtLeastOneChecked implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function passes($attribute, $value)
    {
        return is_array($value) && count(array_filter($value)) > 0;
    }
    public function message()
    {
        return 'Please select at least one option.';
    }
     // This is the missing validate method
     public function validate($attribute, $value, $parameters, $validator)
     {  
      
        if (!$validator->errors()->has($attribute)) {
            if (!$this->passes($attribute, $value)) {
                $validator->errors()->add($attribute, $this->message());
            }
        }
     }
}
