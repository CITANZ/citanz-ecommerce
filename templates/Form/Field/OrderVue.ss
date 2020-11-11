<div id="cita-ecom-order-holder">
    <div id="cita-ecom-order">
        <input ref="order_data" type="hidden" value="$RawData" />
        <input ref="non_shippable" type="hidden" value="$NonShippable" />
        <template v-if="order_data">
            <h1 class="mb-4"><span>{{order_data.title}}</span> <span class="btn btn-outline-primary">{{order_data.status}}</span></h1>
            <div class="row">
                <article class="col">
                    <div class="row">
                        <div class="col-6">
                            <h2>Shipping</h2>
                            <div v-if="!non_shippable" class="address-detail">
                                <p>{{order_data.shipping.firstname}} {{order_data.shipping.surname}}</p>
                                <p>{{order_data.billing.phone}}</p>
                                <p v-if="order_data.shipping.org">{{order_data.shipping.org}}</p>
                                <p>{{order_data.shipping.apartment ? (order_data.shipping.apartment + ', ') : ''}}{{order_data.shipping.address}}</p>
                                <p>{{order_data.shipping.suburb}}, {{order_data.shipping.town}}, {{order_data.shipping.region}}</p>
                                <p>{{order_data.shipping.country}}, {{order_data.shipping.postcode}}</p>
                            </div>
                            <p v-else>
                                This order does not contain any shippable item
                            </p>
                        </div>
                        <div class="col-6">
                            <h2>Billing</h2>
                            <div class="address-detail">
                                <p>{{order_data.billing.firstname}} {{order_data.billing.surname}}</p>
                                <p>{{order_data.billing.email}}</p>
                                <p>{{order_data.billing.phone}}</p>
                                <p v-if="order_data.billing.org">{{order_data.billing.org}}</p>
                                <p>{{order_data.billing.apartment ? (order_data.billing.apartment + ', ') : ''}}{{order_data.billing.address}}</p>
                                <p>{{order_data.billing.suburb ? `${ order_data.billing.suburb },` : ''}} {{order_data.billing.town}}, {{order_data.billing.region}}</p>
                                <p>{{order_data.billing.country}}, {{order_data.billing.postcode}}</p>
                            </div>
                        </div>
                        <div class="col-12 mt-4 mb-4">
                            <h2 class="title is-5">Email</h2>
                            <p>{{order_data.email}}</p>
                        </div>
                    </div>
                    <table class="table is-fullwidth">
                        <thead>
                            <tr>
                                <th>Delivered</th>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right" style="width: 15%;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in order_data.cart.items">
                                <td><template v-if="!item.variants || !item.variants.length">{{ item.delivered ? 'Delivered' : 'Pending' }}</template><template v-else>-</template></td>
                                <td>
                                    <p>{{ item.title }}</p>
                                    <ul class="bundled-variants" v-if="item.variants && item.variants.length">
                                        <li v-for="variant in item.variants">[{{ variant.delivered ? 'Delivered' : 'Pending' }}] {{ variant.title }} x {{ variant.count }}</li>
                                    </ul>
                                </td>
                                <td class="text-center">{{item.quantity}}</td>
                                <td class="text-right">
                                    {{ (item.price * item.quantity).toDollar() }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr v-if="order_data.cart.discount">
                                <td colspan="3" class="text-right">{{order_data.cart.discount.title}}: {{order_data.cart.discount.desc}}</td>
                                <td class="text-right">-{{order_data.cart.discount.amount.toDollar()}}</td>
                            </tr>
                            <tr v-if="order_data.cart.gst && order_data.cart.gst.toFloat()">
                                <td colspan="3" class="text-right">GST</td>
                                <td class="text-right">{{order_data.cart.gst.toDollar()}}</td>
                            </tr>
                            <tr v-if="order_data.freight">
                                <td colspan="3" class="text-right">Shipping: {{ order_data.freight.title }}</td>
                                <td class="text-right">{{ order_data.cart.shipping_cost.toDollar() }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right">Grand Total:</td>
                                <td class="text-right">{{ (order_data.cart.grand_total + order_data.cart.shipping_cost - (order_data.cart.discount ? order_data.cart.discount.amount : 0)).toDollar() }}</td>
                            </tr>
                            <tr v-if="order_data.cart.gst_included && order_data.cart.gst_included.toFloat()">
                                <td colspan="3" class="text-right"><p class="help">Included GST:</p></td>
                                <td class="text-right"><p class="help">{{order_data.cart.gst_included.toDollar()}}</p></td>
                            </tr>
                        </tfoot>
                    </table>
                </article>
                <aside class="col-4">
                    <div class="aside-inner">
                        <template v-if="order_data.payment">
                            <p class="subtitle is-6">Amount <template v-if="order_data.payment.transaction_id">paid</template><template v-else>due</template></p>
                            <p class="h1">{{order_data.payment.amount.toDollar()}}</p>
                            <dl class="payment-details">
                                <dt><strong>Reference No.</strong></dt>
                                <dd>{{order_data.cart.ref}}</dd>
                                <dt v-if="order_data.payment.card_type"><strong>Card Type</strong></dt>
                                <dd v-if="order_data.payment.card_type">{{order_data.payment.card_type.toUpperCase()}}</dd>
                                <dt v-if="order_data.payment.card_number"><strong>Card No.</strong></dt>
                                <dd v-if="order_data.payment.card_number">{{order_data.payment.card_number}}</dd>
                                <dt v-if="order_data.payment.card_expiry"><strong>Card Expiry</strong></dt>
                                <dd v-if="order_data.payment.card_expiry">{{order_data.payment.card_expiry}}</dd>
                                <dt v-if="order_data.payment.card_holder"><strong>Card Holder</strong></dt>
                                <dd v-if="order_data.payment.card_holder">{{order_data.payment.card_holder}}</dd>
                            </dl>
                            <hr />
                            <p class="help"><template v-if="order_data.payment.transaction_id">Paid</template><template v-else>Attempted</template> at {{order_data.payment.created.nzst(true)}}, with <strong>{{order_data.payment.payment_method}}</strong></p>
                        </template>
                        <template v-else>
                            <p class="h2">Free Order</p>
                        </template>
                        <div v-if="order_data.cart.comment">
                            <hr />
                            <div class="content">
                                <p><strong>Comment</strong><br />
                                {{order_data.cart.comment}}<br /><br /></p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </template>
    </div>
</div>
