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
    });
}(jQuery));

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
