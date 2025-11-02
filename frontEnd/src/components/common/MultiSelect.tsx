import React, { useState } from 'react';

interface MultiSelectOption {
  value: string;
  label: string;
}

interface MultiSelectProps {
  label?: string;
  options: MultiSelectOption[];
  value?: string[];
  onChange?: (values: string[]) => void;
  error?: string;
  required?: boolean;
  disabled?: boolean;
  placeholder?: string;
  className?: string;
}

export const MultiSelect: React.FC<MultiSelectProps> = ({
  label,
  options,
  value = [],
  onChange,
  error,
  required = false,
  disabled = false,
  placeholder = 'Sélectionner...',
  className = '',
}) => {
  const [isOpen, setIsOpen] = useState(false);

  const handleToggle = (optionValue: string) => {
    if (disabled) return;
    
    const newValue = value.includes(optionValue)
      ? value.filter(v => v !== optionValue)
      : [...value, optionValue];
    
    onChange?.(newValue);
  };

  const selectedLabels = value.map(v => 
    options.find(opt => opt.value === v)?.label
  ).filter(Boolean);

  return (
    <div className={`space-y-1 ${className}`}>
      {label && (
        <label className="block text-sm font-medium text-secondary-700">
          {label}
          {required && <span className="text-danger-500 ml-1">*</span>}
        </label>
      )}
      <div className="relative">
        <button
          type="button"
          onClick={() => setIsOpen(!isOpen)}
          disabled={disabled}
          className={`
            w-full text-left rounded-lg border-secondary-300 shadow-sm
            focus:border-primary-500 focus:ring-primary-500
            disabled:bg-secondary-50 disabled:text-secondary-500
            px-3 py-2 flex items-center justify-between
            ${error ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-500' : ''}
          `}
        >
          <span className={selectedLabels.length > 0 ? 'text-secondary-900' : 'text-secondary-500'}>
            {selectedLabels.length > 0 ? selectedLabels.join(', ') : placeholder}
          </span>
          <i className={`bi bi-chevron-${isOpen ? 'up' : 'down'} text-secondary-400`}></i>
        </button>
        
        {isOpen && (
          <div className="absolute z-10 w-full mt-1 bg-white border border-secondary-300 rounded-lg shadow-lg max-h-60 overflow-auto">
            {options.map((option) => (
              <label
                key={option.value}
                className="flex items-center px-3 py-2 hover:bg-secondary-50 cursor-pointer"
              >
                <input
                  type="checkbox"
                  checked={value.includes(option.value)}
                  onChange={() => handleToggle(option.value)}
                  className="rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
                />
                <span className="ml-2 text-sm text-secondary-700">{option.label}</span>
              </label>
            ))}
          </div>
        )}
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
