axios.defaults.headers.common = {
    'X-Requested-With': 'XMLHttpRequest'
};

(function($)
{
    $.entwine('ss', function($)
    {
        $('#cita-ecom-discount-holder').entwine(
        {
            onmatch: initDiscountInferface
        });

        $('#cita-ecom-order-holder').entwine(
        {
            onmatch: initOrderInferface
        });
    });
}(jQuery));



function initOrderInferface() {
    const app = new Vue({
        el: '#cita-ecom-order',
        data: {
            order_data: null,
            non_shippable: null,
            shipping_missing: null,
            billing_missing: null,
            toggle_billing_editor: false,
            toggle_shipping_editor: false,
        },
        mounted() {
            this.order_data = JSON.parse(this.$refs.order_data.value)
            this.non_shippable = this.$refs.non_shippable.value == '0' ? false : true
            this.shipping_missing = this.$refs.incomplete_shipping_address.value == '0' ? false : true
            this.billing_missing = this.$refs.incomplete_billing_address.value == '0' ? false : true

            this.order_data.cart.items.forEach(o => {
                o.delivered = o.delivered == 0 || o.delivered == "0" ? false : true
            })
        },
        methods: {
            updateItemStatus(item) {
                const data = new FormData()
                data.append('vid', item.id)
                data.append('delivered', item.delivered)
                axios.post(
                    `/admin/cita-ecom/api/order/${this.order_data.cart.id}/update_item`,
                    data
                )
            },
            updateShipping() {
                this.toggle_shipping_editor = false
                const data = new FormData()
                for (let key in this.order_data.shipping) {
                    if (this.order_data.shipping[key]) {
                        data.append(key, this.order_data.shipping[key])
                    }
                }
                axios.post(
                    `/admin/cita-ecom/api/order/${this.order_data.cart.id}/update_shipping`,
                    data
                ).then(resp => {
                    this.order_data = resp.data
                    this.order_data.cart.items.forEach(o => {
                        o.delivered = o.delivered == 0 || o.delivered == "0" ? false : true
                    })
                })
            },
            updateBilling() {
                this.toggle_billing_editor = false
                const data = new FormData()
                if (this.order_data.billing.same_addr) {
                    data.append('same_addr', this.order_data.billing.same_addr)
                } else {
                    for (let key in this.order_data.billing) {
                        if (this.order_data.billing[key]) {
                            data.append(key, this.order_data.billing[key])
                        }
                    }
                }
                axios.post(
                    `/admin/cita-ecom/api/order/${this.order_data.cart.id}/update_billing`,
                    data
                ).then(resp => {
                    this.order_data = resp.data
                    this.order_data.cart.items.forEach(o => {
                        o.delivered = o.delivered == 0 || o.delivered == "0" ? false : true
                    })
                })
            }
        }
    });
}

function initDiscountInferface () {

    const app = new Vue({
        el: '#cita-ecom-discount',
        data: {
            search_term: null,
            ticker: null,
            candidates: [],
            products: [],
            discount_id: null,
            existings: [],
            variants: []
        },
        mounted () {
            this.discount_id = this.$refs.discount_id.value
            this.existings = JSON.parse(this.$refs.existings.value)
            this.variants = JSON.parse(this.$refs.variants.value)
        },
        watch: {
            products(nv, ov) {
                console.log(this.products);
            },
            search_term(nv, ov) {
                if (!nv) {
                    this.candidates = [];
                    return false;
                }

                if (this.ticker) {
                    clearTimeout(this.ticker);
                    this.ticker = null;
                }

                this.ticker = setTimeout(() => {
                    const data = new FormData()
                    data.append('term', this.search_term)
                    data.append('discount_id', this.discount_id)
                    axios.post(
                        '/admin/cita-ecom/api/discount/search_product',
                        data
                    ).then(resp => {
                        this.candidates = resp.data;
                    })
                }, 300);
            }
        },
        methods: {
            keydownHandler(e) {
                if (e.key == 'Enter') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    e.stopPropagation();
                    return false;
                } else if (e.key == 'ArrowDown') {
                    if ( document.getElementsByClassName('product-candidate').length) {
                        const first = document.getElementsByClassName('product-candidate')[0]
                        first.focus()
                    }
                } else if (e.key == 'ArrowUp') {
                    if ( document.getElementsByClassName('product-candidate').length) {
                        const last = document.getElementsByClassName('product-candidate')[document.getElementsByClassName('product-candidate').length -1]
                        last.focus()
                    }
                }
            },
            moveSelection(e) {
                if (e.key == 'ArrowDown') {
                    const next = e.target.parentNode.nextSibling ? e.target.parentNode.nextSibling : e.target.parentNode.parentNode.firstChild
                    next.firstChild.focus()
                } else if (e.key == 'ArrowUp') {
                    const next = e.target.parentNode.previousSibling ? e.target.parentNode.previousSibling : e.target.parentNode.parentNode.lastChild
                    next.firstChild.focus()
                }
            },
            hasVariant(id) {
                return this.variants.indexOf(id) >= 0;
            },
            checkVariant(e) {
                const id = e.target.value;
                if (e.target.checked) {
                    this.addVariant(id);
                } else {
                    this.removeVariant(id);
                }
            },
            addVariant(id) {
                const data = new FormData()
                data.append('variant_id', id)
                data.append('discount_id', this.discount_id)
                axios.post(
                    '/admin/cita-ecom/api/discount/add_variant',
                    data
                )
            },
            removeVariant(id) {
                this.variants = this.variants.filter( o => o.id != id)
                const data = new FormData()
                data.append('variant_id', id)
                data.append('discount_id', this.discount_id)
                axios.post(
                    '/admin/cita-ecom/api/discount/remove_variant',
                    data
                )
            },
            addProduct(id) {
                this.candidates = []
                this.search_term = null
                this.products.push(id)
                const data = new FormData()
                data.append('product_id', id)
                data.append('discount_id', this.discount_id)
                axios.post(
                    '/admin/cita-ecom/api/discount/add_product',
                    data
                ).then(resp => {
                    this.existings.push(resp.data)
                    this.existings.sort((a, b) => a.title < b.title ? -1 : 1)
                })
            },
            removeProduct(id) {
                if (confirm('You want to disconnect this product from the discount?')) {
                    this.existings = this.existings.filter( o => o.id != id)
                    const data = new FormData()
                    data.append('product_id', id)
                    data.append('discount_id', this.discount_id)
                    axios.post(
                        '/admin/cita-ecom/api/discount/remove_product',
                        data
                    )
                }
            }
        }
    })
}

Date.prototype.nzst = function(include_time, include_second) {
    var d = this.getDate().DoubleDigit() + '/' + (this.getMonth() + 1).DoubleDigit() + '/' + this.getFullYear(),
        t = '',
        ampm = this.getHours() >= 12 ? 'pm' : 'am',
        hours = this.getHours() % 12;

    hours = hours ? hours : 12;
    t = ' - ' + hours + '.' + this.getMinutes().DoubleDigit();

    if (include_second) {
        t += '.' + this.getSeconds().DoubleDigit();
    }

    t += ampm;

    return d + (include_time ? t : '');
};

String.prototype.nzst = function(include_time, include_second) {
    let d = new Date(this);

    return d.nzst(include_time, include_second);
};

String.prototype.DoubleDigit = function() {
    return this.padStart(2, '0');
};

Number.prototype.DoubleDigit = function() {
    return this.toString().padStart(2, '0')
};


String.prototype.toFloat = function toFloat() {
    var n = this.trim();
    n = n.replace(/\$/gi, '').replace(/,/gi, '');
    if (n.length === 0) {
        return 0;
    }
    return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
};

Number.prototype.toFloat = function toFloat() {
    return this.valueOf();
};

String.prototype.kmark = function() {
    if (this.length === 0) {
        return this;
    }
    var x = this.split('.'),
        x1 = x[0],
        x2 = x.length > 1 ? '.' + x[1] : '',
        rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
};

Number.prototype.kmark = function() {
    var s = this.toString();
    return s.kmark();
};

String.prototype.toDollar = function toDollar(digits) {
    var n           =   this.toFloat(),
        is_minus    =   n < 0;
    n   =   Math.round(n * 100) / 100;
    n   =   Math.abs(n);
    digits = (digits === null || digits === undefined) ? 2 : digits;
    return (is_minus ? '-$' : '$') + n.toFixed(digits).kmark();
};

Number.prototype.toDollar = function toDollar(digits) {
    var n           =   this,
        is_minus    =   n < 0;
    n   =   Math.round(n * 100) / 100;
    n   =   Math.abs(n);
    digits = (digits === null || digits === undefined) ? 2 : digits;
    return (is_minus ? '-$' : '$') + n.toFixed(digits).kmark();
};
