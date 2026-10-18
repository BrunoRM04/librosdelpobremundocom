// La promoción se calcula sobre el precio de catálogo, sin modificarlo en la base.
window.PreciosPobreMundo = Object.freeze({
    descuento: 0.20,
    final(precio) {
        const base = Number(precio);
        return Number.isFinite(base) && base >= 0 ? Math.round(base * 0.80) : 0;
    },
    actualizarCarrito(carrito, catalogo) {
        const porId = new Map(catalogo.map(libro => [String(libro.id), libro]));
        const porTitulo = new Map();
        catalogo.forEach(libro => {
            if (porTitulo.has(libro.titulo)) porTitulo.set(libro.titulo, null);
            else porTitulo.set(libro.titulo, libro);
        });
        return carrito.map(item => {
            const libro = porId.get(String(item.id)) || porTitulo.get(item.titulo);
            if (!libro) return { ...item, precioVerificado: false };
            return { ...item, id: libro.id, precio: this.final(libro.precio), stock: Number(libro.stock), precioVerificado: true };
        });
    }
});
