<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Validation;

use Yangweijie\CWrapper\Exception\ValidationException;

/**
 * Engine for managing and applying validation rules
 */
class ValidationRuleEngine
{
    /**
     * @var array<string, ValidationRule[]> Validation rules by C type
     */
    private array $typeRules = [];

    /**
     * @var array<string, callable> Custom validation functions
     */
    private array $customValidators = [];

    private ParameterValidator $parameterValidator;

    public function __construct(?ParameterValidator $parameterValidator = null)
    {
        $this->parameterValidator = $parameterValidator ?? new ParameterValidator();
        $this->initializeDefaultRules();
    }

    /**
     * Add validation rule for a specific C type
     */
    public function addTypeRule(string $cType, ValidationRule $rule): void
    {
        if (!isset($this->typeRules[$cType])) {
            $this->typeRules[$cType] = [];
        }
        
        $this->typeRules[$cType][] = $rule;
    }

    /**
     * Add custom validation function
     */
    public function addCustomValidator(string $name, callable $validator): void
    {
        $this->customValidators[$name] = $validator;
    }

    /**
     * Get all validation rules for a C type
     *
     * @return ValidationRule[]
     */
    public function getRulesForType(string $cType): array
    {
        return $this->typeRules[$cType] ?? [];
    }

    /**
     * Validate a single parameter against its C type
     */
    public function validateParameter(mixed $value, string $cType): ValidationResult
    {
        $rules = $this->getRulesForType($cType);
        
        // If no specific rules, use basic type validation
        if (empty($rules)) {
            $typeRule = new ValidationRule('type', ['expected_type' => $cType]);
            return $this->parameterValidator->validate($value, $typeRule);
        }

        // Apply all rules for this type
        $errors = [];
        $convertedValue = $value;

        foreach ($rules as $rule) {
            $result = $this->parameterValidator->validate($convertedValue, $rule);
            
            if (!$result->isValid) {
                $errors = array_merge($errors, $result->errors);
            } else {
                // Use converted value from successful validation
                $convertedValue = $result->convertedValue ?? $convertedValue;
            }
        }

        return new ValidationResult(empty($errors), $errors, $convertedValue);
    }   
 /**
     * Validate multiple parameters against their C function signature
     *
     * @param array<mixed> $parameters
     * @param array<string> $cTypes
     * @return ValidationResult
     */
    public function validateFunctionParameters(array $parameters, array $cTypes): ValidationResult
    {
        // First check parameter count
        if (count($parameters) !== count($cTypes)) {
            return new ValidationResult(
                false,
                ["Parameter count mismatch. Expected " . count($cTypes) . ", got " . count($parameters)]
            );
        }

        $errors = [];
        $convertedParameters = [];

        // Validate each parameter
        foreach ($parameters as $index => $parameter) {
            $cType = $cTypes[$index];
            $result = $this->validateParameter($parameter, $cType);

            if (!$result->isValid) {
                $errors[] = "Parameter {$index} ({$cType}): " . implode(', ', $result->errors);
            } else {
                $convertedParameters[] = $result->convertedValue ?? $parameter;
            }
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            empty($errors) ? $convertedParameters : null
        );
    }

    /**
     * Create validation rule from configuration array
     */
    public function createRuleFromConfig(array $config): ValidationRule
    {
        $type = $config['type'] ?? 'type';
        $parameters = $config['parameters'] ?? [];

        // Handle custom validator references
        if ($type === 'custom' && isset($parameters['validator_name'])) {
            $validatorName = $parameters['validator_name'];
            if (isset($this->customValidators[$validatorName])) {
                $parameters['validator'] = $this->customValidators[$validatorName];
            } else {
                throw new ValidationException("Custom validator '{$validatorName}' not found");
            }
        }

        return new ValidationRule($type, $parameters);
    }

    /**
     * Load validation rules from configuration array
     *
     * @param array<string, array> $config Configuration array with type => rules mapping
     */
    public function loadRulesFromConfig(array $config): void
    {
        foreach ($config as $cType => $rulesConfig) {
            if (!is_array($rulesConfig)) {
                continue;
            }

            foreach ($rulesConfig as $ruleConfig) {
                if (!is_array($ruleConfig)) {
                    continue;
                }

                try {
                    $rule = $this->createRuleFromConfig($ruleConfig);
                    $this->addTypeRule($cType, $rule);
                } catch (ValidationException $e) {
                    // Log error but continue loading other rules
                    error_log("Failed to load validation rule for type {$cType}: " . $e->getMessage());
                }
            }
        }
    }    /**
 
    * Initialize default validation rules for common C types
     */
    private function initializeDefaultRules(): void
    {
        // Integer type rules with range validation
        $this->addTypeRule('char', new ValidationRule('type', ['expected_type' => 'char']));
        $this->addTypeRule('char', new ValidationRule('range', ['min' => -128, 'max' => 127]));

        $this->addTypeRule('unsigned char', new ValidationRule('type', ['expected_type' => 'unsigned char']));
        $this->addTypeRule('unsigned char', new ValidationRule('range', ['min' => 0, 'max' => 255]));

        $this->addTypeRule('short', new ValidationRule('type', ['expected_type' => 'short']));
        $this->addTypeRule('short', new ValidationRule('range', ['min' => -32768, 'max' => 32767]));

        $this->addTypeRule('unsigned short', new ValidationRule('type', ['expected_type' => 'unsigned short']));
        $this->addTypeRule('unsigned short', new ValidationRule('range', ['min' => 0, 'max' => 65535]));

        $this->addTypeRule('int', new ValidationRule('type', ['expected_type' => 'int']));
        $this->addTypeRule('unsigned int', new ValidationRule('type', ['expected_type' => 'unsigned int']));

        // Float type rules
        $this->addTypeRule('float', new ValidationRule('type', ['expected_type' => 'float']));
        $this->addTypeRule('double', new ValidationRule('type', ['expected_type' => 'double']));

        // String type rules
        $this->addTypeRule('char*', new ValidationRule('type', ['expected_type' => 'char*']));
        $this->addTypeRule('const char*', new ValidationRule('type', ['expected_type' => 'const char*']));

        // Pointer type rules
        $this->addTypeRule('void*', new ValidationRule('type', ['expected_type' => 'void*']));

        // Boolean type rules
        $this->addTypeRule('bool', new ValidationRule('type', ['expected_type' => 'bool']));
        $this->addTypeRule('_Bool', new ValidationRule('type', ['expected_type' => '_Bool']));
    }

    /**
     * Get all registered custom validators
     *
     * @return array<string, callable>
     */
    public function getCustomValidators(): array
    {
        return $this->customValidators;
    }

    /**
     * Remove validation rule for a type
     */
    public function removeTypeRules(string $cType): void
    {
        unset($this->typeRules[$cType]);
    }

    /**
     * Remove custom validator
     */
    public function removeCustomValidator(string $name): void
    {
        unset($this->customValidators[$name]);
    }

    /**
     * Get all type rules
     *
     * @return array<string, ValidationRule[]>
     */
    public function getAllTypeRules(): array
    {
        return $this->typeRules;
    }
}