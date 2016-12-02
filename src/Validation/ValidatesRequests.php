<?php
namespace Addons\Core\Validation;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Foundation\Validation\ValidatesRequests as BaseValidatesRequests;
trait ValidatesRequests
{
	use BaseValidatesRequests;
	/**
	 * The default error bag.
	 *
	 * @var string
	 */
	
	private function getValidationData($table, $keys, Model $model = null, $replaceToTagName = false)
	{
		$config = app('config')->get('validation.'.$table);
		$keys == '*' && $keys = array_keys($config);
		$validation_data = array_keyfilter($config, $keys);
		$rules = $messages = $attributes = [];
		foreach ($validation_data as $k => $v)
		{
			empty($v['rules']) && $v['rules'] = [];
			!is_array($v['rules']) && $v['rules'] = explode('|', $v['rules']);
			foreach ($v['rules'] as &$vv)
			{
				//替换rule中的{{  }}
				$vv = str_replace(',{{attribute}}', ','.$k, $vv);
				$vv = preg_replace_callback('/,\{\{([a-z0-9_\-]*)\}\}/i', function( $matches ) use ($model){
					return !empty($model) ? ($model->offsetExists($matches[1]) ?  ','.$model->getAttribute($matches[1]) : '') : '';
				}, $vv);
			}

			isset($v['tag_name']) && $replaceToTagName && $k = $v['tag_name'];
			isset($v['rules']) && $rules[$k] = $v['rules'];
			isset($v['message']) && $messages[$k] = $v['message'];
			isset($v['name']) && $attributes[$k] = $v['name'];
		}

		return compact('rules', 'messages', 'attributes');
	}
	/**
	 * [validate description]
	 * @param  Request $request [description]
	 * @param  [type]  $table   [description]
	 * @param  string  $keys    [description]
	 * @return [type]           [description]
	 */
	protected function validating(Request $request, $table, $keys = '*', Model $model = null)
	{
		$validateData = $this->getValidationData($table, $keys, $model);
		$validator = $this->getValidationFactory()->make($request->all(), $validateData['rules'], array_keyflatten($validateData['messages'], '.'), $validateData['attributes']);
		return $validator;
	}

	public function getScriptValidate($table, $keys = '*', Model $model = null)
	{
		$validateData = $this->getValidationData($table, $keys, $model, true);
		$validator = $this->getValidationFactory()->make([], $validateData['rules'], $validateData['messages'], $validateData['attributes']);
		$rules = $validator->getjQueryRules();

		return ['rules' => $rules, 'messages' => $validateData['messages']];
	}
	/**
	 * [autoValidate description]
	 * @param  Request $request [description]
	 * @param  [type]  $table   [description]
	 * @param  string  $keys    [description]
	 * @return [type]           [description]
	 */
	public function autoValidate(Request $request, $table, $keys = '*', Model $model = null)
	{
		if ($request->ajax() || $request->offsetExists('of')) return $this->validateWithApi($request, $table, $keys, $model);

		$validator = $this->validating($request, $table, $keys);
		if ($validator->fails())
			$this->throwValidationException($request, $validator);

		return $this->filterValidatorData($validator, $keys);;
	}
	/**
	 * [validateWithApi description]
	 * @param  Request $request [description]
	 * @param  [type]  $table   [description]
	 * @param  string  $keys    [description]
	 * @return [type]           [description]
	 */
	public function validateWithApi(Request $request, $table, $keys = '*', Model $model = null)
	{
		$validator = $this->validating($request, $table, $keys, $model);
		return $validator->fails() ? $this->failure_validate($validator->errors()) : $this->filterValidatorData($validator, $keys);
	}
	/**
	 * [filterValidatorData description]
	 * @param  Validator $validator [description]
	 * @param  [type]    $keys      [description]
	 * @return [type]               [description]
	 */
	private function filterValidatorData(Validator $validator, $keys)
	{
		$data = $validator->getData();
		return $keys == '*' ? $data : array_keyfilter($data, $keys);
	}
}
