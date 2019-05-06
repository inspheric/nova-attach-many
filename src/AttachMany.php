<?php

namespace NovaAttachMany;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Authorizable;
use Laravel\Nova\Fields\FormatsRelatableDisplayValues;

use NovaAttachMany\Rules\ArrayRules;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;

class AttachMany extends Field
{
    use Authorizable;
    use FormatsRelatableDisplayValues;

    public $showOnIndex = false;

    public $showOnDetail = true;

    public $component = 'nova-attach-many';

    /**
     * Indicates if the related resources can be viewed.
     *
     * @var bool
     */
    public $viewable = true;

    public $related = [];

    /**
     * Indicates the maximum number of items to show on the detail view.
     *
     * @var bool
     */
    public $limit;

    /**
     * Indicates whether related resources are shown as chips on the detail view.
     *
     * @var bool
     */
    public $chips = false;

    /**
     * The column that should be displayed for the field.
     *
     * @var \Closure
     */
    public $display;

    public function __construct($name, $attribute = null, $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);

        $this->resource = $resource;

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->manyToManyRelationship = $this->attribute;

        $this->fillUsing(function($request, $model, $attribute, $requestAttribute) use($resource) {
            if(is_subclass_of($model, 'Illuminate\Database\Eloquent\Model')) {
                $model::saved(function($model) use($attribute, $request) {
                    $model->$attribute()->sync(
                        json_decode($request->$attribute, true)
                    );
                });

                unset($request->$attribute);
            }
        });
    }

    public function rules($rules)
    {
        $rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        $this->rules = [ new ArrayRules($rules) ];

        return $this;
    }

    public function resolve($resource, $attribute = null)
    {
        $results = null;

        if ($resource->relationLoaded($this->attribute)) {
            $results = $resource->getRelation($this->attribute);
        }

        if (! $results) {
            $results = $resource->{$this->attribute}()->withoutGlobalScopes()->getResults(); //FIXME
        }

        $this->value = $results->map->getKey();

        $this->related = $results->map(function($value) {

            $resource = new $this->resourceClass($value);
            $request = app(NovaRequest::class);

            return [
                'value' => $value->getKey(),
                'display' => $this->formatDisplayValue($resource),
                'avatar' => $resource->resolveAvatarUrl($request),
                'viewable' => $this->viewable && $resource->authorizedToView($request),
            ];

        });
    }

    public function authorize(Request $request)
    {
        if(! $this->resourceClass::authorizable()) {
            return true;
        }

        if(! isset($request->resource)) {
            return false;
        }

        return call_user_func([ $this->resourceClass, 'authorizedToViewAny'], $request)
            && $request->newResource()->authorizedToAttachAny($request, $this->resourceClass::newModel())
            && parent::authorize($request);
    }

    /**
     * Specify if the related resource can be viewed.
     *
     * @param  bool  $value
     * @return $this
     */
    public function viewable($value = true)
    {
        $this->viewable = $value;

        return $this;
    }

    /**
     * Specify the maximum number of items to display on the detail view.
     *
     * @param  int  $limit
     * @return $this
     */
    public function limit(int $limit = 5)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Specify that the related resources should be displayed as chips on the detail view.
     *
     * @param  bool|string  $chips
     * @return $this
     */
    public function chips($chips = true)
    {
        $this->chips = ($chips === 'square' ? 'square' : (bool) $chips);

        return $this;
    }

    /**
     * Specify that all items should be displayed on the detail view.
     *
     * @return $this
     */
    public function unlimited()
    {
        $this->limit = 0;

        return $this;
    }

    public function publicFormatDisplayValue($resource)
    {
        return $this->formatDisplayValue($resource);
    }

    /**
     * Get additional meta information to merge with the field payload.
     *
     * @return array
     */
    public function meta()
    {
        return array_merge([
            'chips' => $this->chips,
            'related' => $this->related,
            'limit' => $this->limit,
            'resourceName' => $this->resourceName,
            'viewable' => $this->viewable,
        ], $this->meta);
    }
}
