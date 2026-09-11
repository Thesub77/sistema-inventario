export function categoriasModule() {
    return {
        // Categories state
        showCategoryModal: false,
        isEditingCategory: false,
        isSavingCategory: false,
        categoryForm: {
            categoria_id: null,
            codigo_categoria: '',
            nombre_categoria: '',
            descripcion_categoria: '',
            estado: 1,
        },

        // Category CRUD Methods
        openCategoryModal(cat = null) {
            if (cat) {
                this.isEditingCategory = true;
                this.categoryForm = {
                    ...cat
                };
            } else {
                this.isEditingCategory = false;
                this.categoryForm = {
                    categoria_id: null,
                    codigo_categoria: `CAT-${String(this.categorias.length + 1).padStart(2, '0')}`,
                    nombre_categoria: '',
                    descripcion_categoria: '',
                    estado: 1
                };
            }
            this.showCategoryModal = true;
        },

        async saveCategory() {
            if (this.isSavingCategory) return;
            this.isSavingCategory = true;

            const url = this.isEditingCategory ?
                `/api/categorias/${this.categoryForm.categoria_id}` :
                '/api/categorias';
            const method = this.isEditingCategory ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.categoryForm)
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    let errorMsg = errData.message || 'Error al procesar la categoría';
                    if (errData.errors) {
                        errorMsg = Object.values(errData.errors).flat().join('<br>');
                    }
                    throw new Error(errorMsg);
                }

                this.showCategoryModal = false;
                await this.refreshAll();
                Swal.fire({
                    icon: 'success',
                    title: '¡Categoría guardada!',
                    background: '#1e293b',
                    color: '#fff'
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    html: error.message,
                    background: '#1e293b',
                    color: '#fff'
                });
            } finally {
                this.isSavingCategory = false;
            }
        },

        async deleteCategory(cat) {
            const result = await Swal.fire({
                title: '¿Eliminar categoría?',
                text: `Se eliminará "${cat.nombre_categoria}"`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                background: '#1e293b',
                color: '#fff'
            });

            if (result.isConfirmed) {
                await fetch(`/api/categorias/${cat.categoria_id}`, {
                    method: 'DELETE'
                });
                this.refreshAll();
            }
        }
    };
}
