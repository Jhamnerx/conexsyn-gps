<script src='https://www.google.com/recaptcha/api.js?render={{ settings('main_settings.recaptcha_site_key') }}' async defer></script>

<input type="hidden" id="recaptcha3" name="g-recaptcha-response">

<script>
    $(document).ready(function() {
        let recaptcha = $('#recaptcha3');

        recaptcha.closest('form').on('submit', function (e) {
            let form = this;

            e.preventDefault();

            grecaptcha.ready(function() {
                grecaptcha.execute('{{ settings('main_settings.recaptcha_site_key') }}', {action: 'submit'}).then(function(token) {
                    recaptcha.val(token);
                    form.submit();
                });
            });
        });
    });
</script>