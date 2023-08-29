(function($) {

    var VerifyForm = Garnish.Base.extend({
        $form: null,
        $authenticationCodeInput: null,
        $submitBtn: null,
        $spinner: null,
        $errors: null,

        loading: false,

        init: function() {
            this.$form = $('#verify-form'),
            this.$authenticationCodeInput = $('#authenticationCode');
            this.$submitBtn = $('#submit');
            this.$errors = $('#login-errors');
            
            this.submitBtn = new Garnish.MultiFunctionBtn(this.$submitBtn, {
                changeButtonText: true,
            });

            this.addListener(this.$authenticationCodeInput, 'input', 'onInput');
            this.addListener(this.$form, 'submit', 'onSubmit');

            // Focus first empty field in form
            if (!Garnish.isMobileBrowser()) {
                this.$authenticationCodeInput.focus();
            }
        },

        validate: function() {
            if (!this.$authenticationCodeInput.val()) {
                return Craft.t('app', 'Authentication code is invalid.');
            }

            return true;
        },

        onInput: function (event) {
            if (this.validateOnInput && this.validate() === true) {
                this.clearErrors();
            }
        },

        onSubmit: function(event) {
            // Prevent full HTTP submits
            event.preventDefault();

            const error = this.validate();
            if (error !== true) {
                this.showError(error);
                this.validateOnInput = true;
                return;
            }

            this.submitBtn.busyEvent();

            this.clearErrors();

            var data = {
                authenticationCode: this.$authenticationCodeInput.val()
            };

            Craft.sendActionRequest('POST', this.$form.attr('data-action'), {data})
                .then((response) => {
                    this.submitBtn.successEvent();
                    window.location.href = response.data.returnUrl;
                })
                .catch(({response}) => {
                    Garnish.shake(this.$form, 'left');
                    this.onSubmitResponse();
            
                    // Add the error message
                    this.showError(response.data.message);
                });
    
            return false;
        },

        onSubmitResponse: function() {
            this.submitBtn.failureEvent();
        },

        showError: function (error) {
            this.clearErrors();

            $('<p style="display: none;">' + error + '</p>')
                .appendTo(this.$errors)
                .velocity('fadeIn');
        },

        clearErrors: function () {
            this.$errors.empty();
        },
    });

    new VerifyForm();
})(jQuery);
