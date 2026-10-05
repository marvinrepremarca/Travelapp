// Navegación por teclado del autocompletar (resources/views/components/ui/combobox.blade.php).
// Las sugerencias las trae Livewire; aquí solo se mueve la opción activa y se elige con Enter.
const NONE = -1;

export default () => ({
    open: false,
    active: NONE,

    options() {
        return this.$refs.listbox ? Array.from(this.$refs.listbox.querySelectorAll('[role="option"]')) : [];
    },

    hasOptions() {
        return this.options().length > 0;
    },

    activeId(listbox) {
        return this.open && this.active !== NONE ? `${listbox}-${this.active}` : null;
    },

    move(step) {
        const total = this.options().length;
        if (total === 0) {
            return;
        }

        this.open = true;
        this.active = (this.active + step + total) % total;
    },

    pick(event) {
        const option = this.options()[this.active];
        if (!this.open || option === undefined) {
            return;
        }

        event.preventDefault();
        option.click();
    },

    reset() {
        this.open = true;
        this.active = NONE;
    },

    close() {
        this.open = false;
        this.active = NONE;
    },
});
