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
            existings: []
        },
        mounted () {
            this.discount_id = this.$refs.discount_id.value
            const dict = JSON.parse(this.$refs.existings.value)

            for (let key in dict) {
                this.existings.push({
                    id: key,
                    title: dict[key]
                })
            }
        },
        watch: {
            products(nv, ov) {
                console.log(this.products);
            },
            search_term(nv, ov) {
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
            addProduct(id) {
                this.candidates = []
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
