(function($) {

    var VerifyForm = Garnish.Base.extend({
        $form: null,
        $authenticationCodeInput: null,
        $submitBtn: null,
        $spinner: null,
        $error: null,

        loading: false,

        init: function()
        {
            this.$form = $('#verify-form'),
            this.$authenticationCodeInput = $('#authenticationCode');
            this.$submitBtn = $('#submit');
            this.$spinner = $('#spinner');

            this.addListener(this.$authenticationCodeInput, 'textchange', 'validate');
            this.addListener(this.$form, 'submit', 'onSubmit');
        },

        validate: function() {
            if (this.$authenticationCodeInput.val()) {
                this.$submitBtn.enable();
                return true;
            } else {
                this.$submitBtn.disable();
                return false;
            }
        },

        onSubmit: function(event) {
            // Prevent full HTTP submits
            event.preventDefault();

            if (!this.validate()) return;

            this.$submitBtn.addClass('active');
            this.$spinner.removeClass('hidden');
            this.loading = true;

            if (this.$error) {
                this.$error.remove();
            }

            var data = {
                authenticationCode: this.$authenticationCodeInput.val()
            };

            Craft.postActionRequest(this.$form.attr('action'), data, $.proxy(function(response, textStatus) {
                if (textStatus == 'success') {
                    if (response.success) {
                        window.location.href = Craft.getUrl(response.returnUrl);
                    } else {
                        Garnish.shake(this.$form);
                        this.onSubmitResponse();

                        // Add the error message
                        this.showError(response.error);
                    }
                } else {
                    this.onSubmitResponse();
                }

            }, this));

            return false;
        },

        onSubmitResponse: function() {
            this.$submitBtn.removeClass('active');
            this.$spinner.addClass('hidden');
            this.loading = false;
        },

        showError: function(error) {
            if (!error) {
                error = Craft.t('An unknown error occurred.');
            }

            this.$error = $('<p class="error" style="display:none">' + error + '</p>').insertAfter($('.buttons', this.$form));
            this.$error.velocity('fadeIn');
        }
    });

    var verifyForm = new VerifyForm();

})(jQuery);
