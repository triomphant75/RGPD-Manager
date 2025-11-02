import React from 'react';

interface InputProps {
  label?: string;
  type?: string;
  placeholder?: string;
  value?: string;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  error?: string;
  required?: boolean;
  disabled?: boolean;
  className?: string;
  icon?: string;
}

export const Input: React.FC<InputProps> = ({
  label,
  type = 'text',
  placeholder,
  value,
  onChange,
  error,
  required = false,
  disabled = false,
  className = '',
  icon,
}) => {
  return (
    <div className={`space-y-1 ${className}`}>
      {label && (
        <label className="block text-sm font-medium text-secondary-700">
          {label}
          {required && <span className="text-danger-500 ml-1">*</span>}
        </label>
      )}
      <div className="relative">
        {icon && (
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i className={`bi ${icon} text-secondary-400`}></i>
          </div>
        )}
        <input
          type={type}
          placeholder={placeholder}
          value={value}
          onChange={onChange}
          disabled={disabled}
          required={required}
          className={`
            block w-full rounded-lg border-secondary-300 shadow-sm
            focus:border-primary-500 focus:ring-primary-500
            disabled:bg-secondary-50 disabled:text-secondary-500
            ${icon ? 'pl-10' : 'pl-3'} pr-3 py-2
            ${error ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-500' : ''}
          `}
        />
      </div>
      {error && (
        <p className="text-sm text-danger-600 flex items-center">
          <i className="bi bi-exclamation-circle mr-1"></i>
          {error}
        </p>
      )}
    </div>
  );
};
