<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function failedAuthorization()
    {
        // \Illuminate\Auth\Access\AuthorizationException
        throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('操作未授权.');
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // protected function failedValidation(Validator $validator)
    // {
    //     Dingo\Api\Exception\DeleteResourceFailedException
    //     Dingo\Api\Exception\ResourceException
    //     Dingo\Api\Exception\StoreResourceFailedException
    //     Dingo\Api\Exception\UpdateResourceFailedException

    //     throw (new ValidationException($validator))
    //                 ->errorBag($this->errorBag)
    //                 ->redirectTo($this->getRedirectUrl());
    // }
}
