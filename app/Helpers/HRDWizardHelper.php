<?php

namespace App\Helpers;

/**
 * HRD Wizard Helper Functions
 *
 * Helper functions for Employee Wizard form handling.
 */

if (!function_exists('wizard_old_input')) {
    /**
     * Get old input value with fallback to model data
     *
     * @param string $key The input key (e.g., 'user.name', 'employee.full_name')
     * @param mixed $employee The employee model instance
     * @param mixed $user The user model instance (optional)
     * @param string $default Default value if no value found
     * @return mixed
     */
    function wizard_old_input(string $key, $employee = null, $user = null, string $default = '')
    {
        // 1. Prioritas: old input (dari validation error)
        $value = old($key);
        if ($value !== null && $value !== '') {
            return $value;
        }

        // 2. Handle user.xxx keys - access from User object (e.g., user.name, user.email)
        if (str_starts_with($key, 'user.')) {
            $field = substr($key, 5); // Remove 'user.' prefix
            if ($user && isset($user->{$field})) {
                return $user->{$field};
            }
            return $default;
        }

        // 3. Handle employee.xxx keys - access from Employee object (e.g., employee.full_name)
        if (str_starts_with($key, 'employee.')) {
            $field = substr($key, 10); // Remove 'employee.' prefix (10 chars)
            if ($employee && isset($employee->{$field})) {
                return $employee->{$field};
            }
            return $default;
        }

        // 4. Direct field access
        if ($employee && isset($employee->{$key})) {
            return $employee->{$key};
        }

        return $default;
    }
}

if (!function_exists('get_wizard_user_data')) {
    /**
     * Get user data from employee model
     *
     * @param mixed $employee The employee model instance
     * @return mixed|null
     */
    function get_wizard_user_data($employee = null)
    {
        if ($employee && $employee->relationLoaded('user')) {
            return $employee->user;
        }
        return null;
    }
}

if (!function_exists('is_wizard_edit_mode')) {
    /**
     * Check if wizard is in edit mode
     *
     * @param mixed $mode The mode variable
     * @return bool
     */
    function is_wizard_edit_mode($mode): bool
    {
        return isset($mode) && $mode === 'edit';
    }
}

if (!function_exists('wizard_step2_old_input')) {
    /**
     * Get old input value for Step 2 (Employment) with fallback to model data
     *
     * @param string $key The input key
     * @param mixed $employee The employee model instance
     * @param string $default Default value if no value found
     * @return mixed
     */
    function wizard_step2_old_input(string $key, $employee = null, string $default = '')
    {
        // 1. Prioritas: old input (dari validation error)
        $value = old($key);
        if ($value !== null && $value !== '') {
            return $value;
        }

        // 2. Handle employee.xxx keys
        if (str_starts_with($key, 'employee.')) {
            $field = substr($key, 10);
            if ($employee && isset($employee->{$field})) {
                return $employee->{$field};
            }
            return $default;
        }

        // 3. Direct field access
        if ($employee && isset($employee->{$key})) {
            return $employee->{$key};
        }

        return $default;
    }
}

if (!function_exists('wizard_step3_old_input')) {
    /**
     * Get old input value for Step 3 (Placement) with fallback to model data
     *
     * @param string $key The input key
     * @param mixed $employee The employee model instance
     * @param mixed $placement The placement model instance
     * @param string $default Default value if no value found
     * @return mixed
     */
    function wizard_step3_old_input(string $key, $employee = null, $placement = null, string $default = '')
    {
        // 1. Prioritas: old input (dari validation error)
        $value = old($key);
        if ($value !== null && $value !== '') {
            return $value;
        }

        // 2. Handle placement.xxx keys
        if (str_starts_with($key, 'placement.')) {
            $field = substr($key, 10);
            if ($placement && isset($placement->{$field})) {
                return $placement->{$field};
            }
            return $default;
        }

        // 3. Handle employee.xxx keys
        if (str_starts_with($key, 'employee.')) {
            $field = substr($key, 10);
            if ($employee && isset($employee->{$field})) {
                return $employee->{$field};
            }
            return $default;
        }

        // 4. Direct field access
        if ($employee && isset($employee->{$key})) {
            return $employee->{$key};
        }

        return $default;
    }
}

if (!function_exists('wizard_step6_old_input')) {
    /**
     * Get old input value for Step 6 (Payroll) with fallback to model data
     *
     * @param string $key The input key
     * @param mixed $salary The salary model instance
     * @param mixed $employee The employee model instance
     * @param string $default Default value if no value found
     * @return mixed
     */
    function wizard_step6_old_input(string $key, $salary = null, $employee = null, string $default = '')
    {
        // 1. Prioritas: old input (dari validation error)
        $value = old($key);
        if ($value !== null && $value !== '') {
            return $value;
        }

        // Map form field names to actual column names
        $fieldMap = [
            'payroll.basic_salary' => 'basic_salary',
            'payroll.payment_method' => 'payment_method',
            'payroll.bank_name' => 'bank_name',
            'payroll.bank_account' => 'bank_account_number',
            'payroll.bank_account_name' => 'bank_account_holder',
        ];

        // 2. Handle payroll.xxx keys
        if (str_starts_with($key, 'payroll.')) {
            $mappedField = $fieldMap[$key] ?? substr($key, 8);

            // Check salary object
            if ($salary && isset($salary->{$mappedField})) {
                return $salary->{$mappedField};
            }

            // Fallback to employee bank fields
            if ($employee) {
                if ($mappedField === 'bank_name' && $employee->bank_name) {
                    return $employee->bank_name;
                }
                if ($mappedField === 'bank_account_number' && $employee->bank_account_number) {
                    return $employee->bank_account_number;
                }
                if ($mappedField === 'bank_account_holder' && $employee->bank_account_holder) {
                    return $employee->bank_account_holder;
                }
            }

            return $default;
        }

        // 3. Handle salary.xxx keys
        if (str_starts_with($key, 'salary.')) {
            $field = substr($key, 7);
            if ($salary && isset($salary->{$field})) {
                return $salary->{$field};
            }
            return $default;
        }

        // 4. Handle employee.xxx keys
        if (str_starts_with($key, 'employee.')) {
            $field = substr($key, 10);
            if ($employee && isset($employee->{$field})) {
                return $employee->{$field};
            }
            return $default;
        }

        // 5. Direct field access
        if ($salary && isset($salary->{$key})) {
            return $salary->{$key};
        }

        if ($employee && isset($employee->{$key})) {
            return $employee->{$key};
        }

        return $default;
    }
}
