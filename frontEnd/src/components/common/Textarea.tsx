import React from 'react';

interface TextareaProps {
  label?: string;
  placeholder?: string;
  value?: string;
  onChange?: (e: React.ChangeEvent<HTMLTextAreaElement>) => void;
  error?: string;
  required?: boolean;
  disabled?: boolean;
  rows?: number;
  className?: string;
}

export const Textarea: React.FC<TextareaProps> = ({
  label,
  placeholder,
  value,
  onChange,
  error,
  required = false,
  disabled = false,
  rows = 3,
  className = '',
}) => {
  return (
    <div className={`space-y-1 ${className}`}>
      {label && (
        <label className="block text-sm font-medium text-secondary-700">
          {label}
          {required && <span className="text-danger-500 ml-1">*</span>}
        </label>
      )}
      <textarea
        placeholder={placeholder}
        value={value}
        onChange={onChange}
        disabled={disabled}
        required={required}
        rows={rows}
        className={`
          block w-full rounded-lg border-secondary-300 shadow-sm
          focus:border-primary-500 focus:ring-primary-500
          disabled:bg-secondary-50 disabled:text-secondary-500
          px-3 py-2 resize-vertical
          ${error ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-500' : ''}
        `}
      />
      {error && (
        <p className="text-sm text-danger-600 flex items-center">
          <i className="bi bi-exclamation-circle mr-1"></i>
          {error}
        </p>
      )}
    </div>
  );
};
