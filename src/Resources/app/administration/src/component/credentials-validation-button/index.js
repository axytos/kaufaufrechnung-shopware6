import template from './credentials-validation-button.html.twig';

const { Component, Mixin } = Shopware;

Component.register('credentials-validation-button', {
    template,

    props: ['label'],
    inject: ['AxytosCredentalsValidatior'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    computed: {
        pluginConfig() {
            let $parent = this.$parent;

            while ($parent.actualConfigData === undefined) {
                $parent = $parent.$parent;
            }

            return $parent.actualConfigData.null;
        }
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        check() {
            this.isLoading = true;
            this.AxytosCredentalsValidatior.check(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('credentials-validation-notification.title'),
                        message: this.$tc('credentials-validation-notification.success-text')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('credentials-validation-notification.title'),
                        message: this.$tc('credentials-validation-notification.error-text')
                    });
                }

                this.isLoading = false;
            });
        }
    }
})