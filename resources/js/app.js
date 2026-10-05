import './bootstrap';
import combobox from './components/combobox';

// Livewire 3 trae Alpine: los componentes se registran cuando arranca.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('combobox', combobox);
});
