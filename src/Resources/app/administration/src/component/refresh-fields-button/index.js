// refresh-fields-button/index.js
import template from './refresh-fields-button.html.twig';
const { Component, Mixin } = Shopware;

const STORAGE_KEY = 'axytos-order-line-fields';
const KEY = 'AxytosKaufAufRechnung.config.refundColumn';

Component.register('refresh-fields-button', {
    template,
    mixins: [Mixin.getByName('notification')],

    props: {
      // MUST be present if you want standard system-config saving
        config: { type: Object, required: false, default: null },
        currentSalesChannelId: { type: [String, null], default: null },
            },

            data() {
                return {
                    isLoading: false,
                    selectOptions: [],
            // fallback if config is not passed (component not inside sw-system-config)
                    localRefundColumn: ''
                };
            },

            computed: {
                parentSystemConfig() {
            // Suche das übergeordnete sw-system-config-Component
                    let $parent = this.$parent;
                    while ($parent && $parent.actualConfigData === undefined) {
                        $parent = $parent.$parent;
                    }
                    return $parent || null;
                },

                refundColumn: {
                    get() {
                        // Fall 1: Komponente wird über Extension mit config-Prop verwendet
                        if (this.config) {
                            return this.config[KEY] ?  ? '';
                        }

                        // Fall 2: Komponente kommt aus config.xml (kein config-Prop)
                        const parent = this.parentSystemConfig;
                        if (!parent || !parent.actualConfigData) {
                            return this.localRefundColumn;
                        }

                        const scope = parent.currentSalesChannelId || 'null';
                        const scopeData = parent.actualConfigData[scope] || {};

                        return scopeData[KEY] ?  ? '';
                    },

                    set(v) {
                        const value = v ?  ? '';

                        // Fall 1: mit config-Prop -> direkt ins config-Objekt schreiben
                        if (this.config) {
                            this.config[KEY] = value;
                            return;
                        }

                        // Fall 2: aus config.xml -> in actualConfigData schreiben
                        const parent = this.parentSystemConfig;
                        if (!parent) {
                            this.localRefundColumn = value;
                            return;
                        }

                        const scope = parent.currentSalesChannelId || 'null';

                        if (!parent.actualConfigData[scope]) {
                              parent.actualConfigData[scope] = {};
                        }

                        parent.actualConfigData[scope][KEY] = value;
                        this.localRefundColumn = value;
                    }
                }
            },

            async created() {
                  console.log('[refresh-fields-button] has form state?', !!this.config, this.config ? .[KEY]);

                  // show cached options right away so the current value can render a label
                  this.loadOptionsFromStorage();
                  this.ensureSelectedOptionVisible();
                  await this.fetchOptions();
            },

            methods: {
                  onUpdateValue(payload) {
                        const next = (payload && typeof payload === 'object') ? payload.value : payload;
                        console.log(
                            '[select] update:value payload =',
                            payload,
                            '→ next =',
                            next,
                            '| hasConfig =',
                            !!this.config
                        );
                  this.refundColumn = next ?  ? '';
                },

                  async fetchOptions() {
                        this.isLoading = true;
                        try {
                            const { data } = await Shopware.Service('syncService').httpClient.post(
                                '/_action/order-line-column-refresher/refresh-fields',
                                {}
                            );
                            if (data ? .success && Array.isArray(data.fields)) {
                                    const val = this.refundColumn;
                                    const fresh = data.fields;
                                    const withoutDup = val ? fresh.filter(o => o ? .value !== val) : fresh;
                                    this.selectOptions = val ? [{ label : val, value : val }, ...withoutDup] : withoutDup;
                                    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(this.selectOptions));
                            }
                        } finally {
                              this.isLoading = false;
                        }
                },

                  loadOptionsFromStorage() {
                        try {
                            const raw = sessionStorage.getItem(STORAGE_KEY);
                            if (!raw) {
                                return;
                            }
                            const parsed = JSON.parse(raw);
                            if (Array.isArray(parsed)) {
                                this.selectOptions = parsed;
                            }
                        } catch {}
                },

                  ensureSelectedOptionVisible() {
                        const val = this.refundColumn;
                        if (!val) {
                            return;
                        }
                        if (!this.selectOptions.some(o => o ? .value === val)) {
                                this.selectOptions = [{ label: val, value: val }, ...this.selectOptions];
                        }
                },
            },
            });
