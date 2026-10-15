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
                const cfgProd = this.empresa?.configuracion?.productos || {};
                const sugerirPrefijo = Boolean(cfgProd.categoria_sugerir_prefijo);
                const rawPrefijo = (cfgProd.prefijo_categoria || 'CAT').trim().replace(/-+$/, '');
                const prefijo = rawPrefijo ? `${rawPrefijo}-` : '';
                let maxCat = 0;
                const reCat = new RegExp('^' + prefijo.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + '(\\d+)$', 'i');
                (this.categorias || []).forEach(c => {
                    const m = (c.codigo_categoria || '').match(reCat);
                    if (m) {
                        const n = parseInt(m[1], 10);
                        if (n > maxCat) maxCat = n;
                    }
                });
                const nextCatNum = maxCat > 0 ? maxCat + 1 : (this.categorias || []).length + 1;

                this.categoryForm = {
                    categoria_id: null,
                    codigo_categoria: sugerirPrefijo
                        ? `${prefijo}${String(nextCatNum).padStart(2, '0')}`
                        : '',
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
                const res = await this.apiFetch(url, {
                    method,
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
                this.notify('¡Categoría guardada!', `La categoría "${this.categoryForm.nombre_categoria}" fue guardada.`, 'success');
                await this.fetchCategorias();
            } catch (error) {
                this.notify('Error al guardar categoría', error.message, 'error');
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
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: this.darkMode ? '#1e293b' : '#ffffff',
                color: this.darkMode ? '#fff' : '#0f172a'
            });

            if (result.isConfirmed) {
                try {
                    const res = await this.apiFetch(`/api/categorias/${cat.categoria_id}`, {
                        method: 'DELETE'
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(data.message || 'No se pudo eliminar la categoría.');
                    }
                    this.notify('Categoría Eliminada', `"${cat.nombre_categoria}" fue eliminada.`, 'success');
                    await this.fetchCategorias();
                } catch (error) {
                    this.notify('Error al eliminar categoría', error.message, 'error');
                }
            }
        }
    };
}
