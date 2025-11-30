<?php

declare(strict_types=1);

namespace DmitryBubyakin\NovaMedialibraryField\Fields;

use Laravel\Nova\Fields\Field;

/**
 * BoundingBoxField - Interactive canvas-based bounding box editor
 *
 * Provides visual damage marking capabilities for images in Laravel Nova.
 * Supports multiple bounding boxes with damage type and severity metadata.
 *
 * @author PCR Card Development Team
 *
 * @version 1.0.0
 */
class BoundingBoxField extends Field
{
    /**
     * The field's component
     *
     * @var string
     */
    public $component = 'bounding-box-field';

    /**
     * Image URL or callable
     *
     * @var string|callable|null
     */
    protected $imageUrlCallback = null;

    /**
     * Existing damage assessments
     *
     * @var array|callable|null
     */
    protected $damageAssessmentsCallback = null;

    /**
     * Multiple image URLs
     *
     * @var array|callable|null
     */
    protected $imageUrls = null;

    /**
     * Whether to preserve original image proportions
     */
    protected bool $preserveOriginalProportions = true;

    /**
     * The callback used to determine if the field is readonly.
     *
     * @var (callable(\Laravel\Nova\Http\Requests\NovaRequest):(bool))|bool|null
     */
    public $readonlyCallback = null;

    /**
     * Set the image URL for the editor
     *
     * @param  string|callable  $url
     * @return $this
     */
    public function image($url): self
    {
        $this->imageUrlCallback = $url;

        return $this;
    }

    /**
     * Set existing damage assessments to display
     *
     * @param  array|\Illuminate\Support\Collection|callable  $assessments
     * @return $this
     */
    public function damageAssessments($assessments): self
    {
        $this->damageAssessmentsCallback = $assessments;

        return $this;
    }

    /**
     * Pass multiple image URLs to the editor
     *
     * @param  array|callable  $images
     * @return $this
     */
    public function withImages($images): self
    {
        $this->imageUrls = $images;

        return $this;
    }

    /**
     * Set whether the field is readonly
     *
     * Accepts either a boolean or a callback that receives the NovaRequest.
     * The callback is resolved during jsonSerialize() when the request is available.
     *
     * @return $this
     */
    public function readonly(callable|bool $callback = true): self
    {
        $this->readonlyCallback = $callback;

        return $this;
    }

    /**
     * Determine if the field is readonly
     */
    public function isReadonly(\Laravel\Nova\Http\Requests\NovaRequest $request): bool
    {
        if (is_callable($this->readonlyCallback)) {
            return (bool) call_user_func($this->readonlyCallback, $request);
        }

        return (bool) $this->readonlyCallback;
    }

    /**
     * Enable "addressed" mode for marking damage as fixed
     *
     * @return $this
     */
    public function addressedMode(bool $enabled = true): self
    {
        return $this->withMeta(['addressedMode' => $enabled]);
    }

    /**
     * Set the API endpoint for updating addressed status
     *
     * @return $this
     */
    public function addressedEndpoint(string $endpoint): self
    {
        return $this->withMeta(['addressedEndpoint' => $endpoint]);
    }

    /**
     * Resolve the field's value for display
     *
     * @param  mixed  $resource
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        $this->resource = $resource;
        $this->value = null; // Computed field
    }

    /**
     * Prepare the field for JSON serialization
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $request = app(\Laravel\Nova\Http\Requests\NovaRequest::class);

        return array_merge(parent::jsonSerialize(), [
            'imageUrl' => $this->resolveImageUrl(),
            'imageUrls' => $this->resolveImageUrls(),
            'damageAssessments' => $this->resolveDamageAssessments(),
            'damageTypes' => $this->getDamageTypeOptions(),
            'severityLevels' => $this->getSeverityLevelOptions(),
            'preserveOriginalProportions' => $this->preserveOriginalProportions,
            'readonly' => $this->isReadonly($request),
        ]);
    }

    /**
     * Resolve the image URL
     */
    protected function resolveImageUrl(): ?string
    {
        if (is_callable($this->imageUrlCallback)) {
            return call_user_func($this->imageUrlCallback);
        }

        return $this->imageUrlCallback;
    }

    /**
     * Resolve multiple image URLs
     *
     * Handles three cases:
     * 1. Callable (closure) - invoke and convert result if needed
     * 2. Collection - convert to array directly
     * 3. Array - return as-is
     */
    protected function resolveImageUrls(): array
    {
        $value = $this->imageUrls;

        // Case 1: Callable (closure) - invoke it to get the actual value
        if (is_callable($value)) {
            $value = call_user_func($value);
        }

        // Case 2: Collection (Support or Eloquent) - convert to array
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->toArray();
        }

        // Case 3: Already an array or null
        return $value ?? [];
    }

    /**
     * Resolve the damage assessments value
     *
     * Handles three cases:
     * 1. Callable (closure) - invoke and convert result if needed
     * 2. Collection - convert to array directly
     * 3. Array - return as-is
     */
    protected function resolveDamageAssessments(): array
    {
        $value = $this->damageAssessmentsCallback;

        // Case 1: Callable (closure) - invoke it to get the actual value
        if (is_callable($value)) {
            $value = call_user_func($value);
        }

        // Case 2: Collection (Support or Eloquent) - convert to array
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->toArray();
        }

        // Case 3: Already an array or null
        return $value ?? [];
    }

    /**
     * Get damage type options for the frontend
     */
    protected function getDamageTypeOptions(): array
    {
        // Load from application constants if available
        if (class_exists('\App\Constants\DamageType')) {
            return $this->getDamageTypesFromApp();
        }

        // Default fallback types
        return $this->getDefaultDamageTypes();
    }

    /**
     * Get severity level options for the frontend
     */
    protected function getSeverityLevelOptions(): array
    {
        // Load from application constants if available
        if (class_exists('\App\Constants\DamageSeverity')) {
            return $this->getSeverityLevelsFromApp();
        }

        // Default fallback severities
        return $this->getDefaultSeverityLevels();
    }

    /**
     * Get damage types from application constants
     */
    protected function getDamageTypesFromApp(): array
    {
        $options = [];
        $damageTypeClass = '\App\Constants\DamageType';

        foreach ($damageTypeClass::all() as $type) {
            $options[] = [
                'value' => $type,
                'label' => $damageTypeClass::label($type),
                'color' => $damageTypeClass::color($type),
                'icon' => $damageTypeClass::icon($type),
            ];
        }

        return $options;
    }

    /**
     * Get severity levels from application constants
     */
    protected function getSeverityLevelsFromApp(): array
    {
        $options = [];
        $severityClass = '\App\Constants\DamageSeverity';

        foreach ($severityClass::all() as $severity) {
            $options[] = [
                'value' => $severity,
                'label' => $severityClass::label($severity),
                'color' => $severityClass::color($severity),
                'priority' => $severityClass::priorityWeight($severity),
            ];
        }

        return $options;
    }

    /**
     * Get default damage types (fallback)
     */
    protected function getDefaultDamageTypes(): array
    {
        return [
            ['value' => 'scratch', 'label' => 'Scratch', 'color' => '#EF4444', 'icon' => "\u{1F52A}"],
            ['value' => 'stain', 'label' => 'Stain', 'color' => '#A855F7', 'icon' => "\u{1F9FD}"],
            ['value' => 'crease', 'label' => 'Crease', 'color' => '#F59E0B', 'icon' => "\u{1F4CF}"],
            ['value' => 'tear', 'label' => 'Tear', 'color' => '#DC2626', 'icon' => "\u{1F4C4}"],
            ['value' => 'edge_wear', 'label' => 'Edge Wear', 'color' => '#3B82F6', 'icon' => "\u{1F532}"],
        ];
    }

    /**
     * Get default severity levels (fallback)
     */
    protected function getDefaultSeverityLevels(): array
    {
        return [
            ['value' => 'minor', 'label' => 'Minor', 'color' => '#22C55E', 'priority' => 1],
            ['value' => 'moderate', 'label' => 'Moderate', 'color' => '#EAB308', 'priority' => 2],
            ['value' => 'severe', 'label' => 'Severe', 'color' => '#EF4444', 'priority' => 3],
        ];
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request
     *
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     */
    protected function fillAttributeFromRequest(
        \Laravel\Nova\Http\Requests\NovaRequest $request,
        $requestAttribute,
        $model,
        $attribute
    ): void {
        if ($request->exists($requestAttribute)) {
            $value = $request[$requestAttribute];

            // Decode JSON if needed
            if (is_string($value)) {
                $value = json_decode($value, true);
            }

            // Ensure it's an array
            if (! is_array($value)) {
                $value = [];
            }

            // Set the attribute (model's mutator will handle validation)
            $model->{$attribute} = $value;
        }
    }
}
