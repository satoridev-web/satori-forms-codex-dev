(function() {
    document.addEventListener('submit', function(event) {
        var form = event.target.closest('.satori-form__form');
        if (!form) {
            return;
        }

        var firstError = form.querySelector('.satori-field__errors li');
        if (firstError) {
            var field = firstError.closest('.satori-field');
            if (field) {
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
})();
