<div id="cita-ecom-order-holder">
    <div id="cita-ecom-order">
        <input ref="order_data" type="hidden" value="$RawData" />
        <input ref="non_shippable" type="hidden" value="$NonShippable" />
        <input ref="incomplete_shipping_address" type="hidden" value="$ShippingAddressIncomplete" />
        <input ref="incomplete_billing_address" type="hidden" value="$BillingAddressIncomplete" />
        <template v-if="order_data">
            <h1 class="mb-4"><span>{{order_data.title}}</span> <span class="btn btn-outline-primary">{{order_data.status}}</span></h1>
            <div class="row">
                <article class="col">
                    <div class="row">
                        <div class="col-6">
                            <h2>Shipping <a v-if="!toggle_shipping_editor" @click.prevent="toggle_shipping_editor = true" class="btn btn-primary btn-sm" href="#" style="font-size: 10px; padding: 0.025em 0.5em; margin-left: 0.5em;">Update</a></h2>
                            <template v-if="!toggle_shipping_editor">
                                <div v-if="!shipping_missing" class="address-detail">
                                    <p>{{order_data.shipping.firstname}} {{order_data.shipping.surname}}</p>
                                    <p v-if="order_data.shipping.phone">{{order_data.shipping.phone}}</p>
                                    <p v-if="order_data.shipping.org">{{order_data.shipping.org}}</p>
                                    <p v-if="order_data.shipping.apartment || order_data.shipping.address">{{order_data.shipping.apartment ? (order_data.shipping.apartment + ', ') : ''}}{{order_data.shipping.address}}</p>
                                    <p v-if="order_data.shipping.suburb || order_data.shipping.town || order_data.shipping.region">{{order_data.shipping.suburb ? `${order_data.shipping.suburb}, `: ''}}{{order_data.shipping.town ? `${order_data.shipping.town}, ` : ''}}{{order_data.shipping.region}}</p>
                                    <p>{{order_data.shipping.country ? `${order_data.shipping.country}, ` : ''}}{{order_data.shipping.postcode}}</p>
                                </div>
                                <p v-else>Shipping address is missing or incomplete</p>
                                <p class="form-text text-muted" v-if="non_shippable">
                                    <em>This order does not contain any shippable item</em>
                                </p>
                            </template>
                            <div v-else class="address-detail__editor">
                                <div class="field">
                                    <label for="shippingFirstname">First name</label>
                                    <input type="text" class="form-control" id="shippingFirstname" v-model="order_data.shipping.firstname" />
                                </div>
                                <div class="field">
                                    <label for="shippingLastname">Last name</label>
                                    <input type="text" class="form-control" id="shippingLastname" v-model="order_data.shipping.surname" />
                                </div>
                                <div class="field">
                                    <label for="shippingPhone">Phone</label>
                                    <input type="text" class="form-control" id="shippingPhone" v-model="order_data.shipping.phone" />
                                </div>
                                <div class="field">
                                    <label for="shippingOrg">Organisation</label>
                                    <input type="text" class="form-control" id="shippingOrg" v-model="order_data.shipping.org" />
                                </div>
                                <div class="field">
                                    <label for="shippingApartment">Apt/Unit/Suite</label>
                                    <input type="text" class="form-control" id="shippingApartment" v-model="order_data.shipping.apartment" />
                                </div>
                                <div class="field">
                                    <label for="shippingAddress">Address</label>
                                    <input type="text" class="form-control" id="shippingAddress" v-model="order_data.shipping.address" />
                                </div>
                                <div class="field">
                                    <label for="shippingSuburb">Suburb</label>
                                    <input type="text" class="form-control" id="shippingSuburb" v-model="order_data.shipping.suburb" />
                                </div>
                                <div class="field">
                                    <label for="shippingCity">City</label>
                                    <input type="text" class="form-control" id="shippingCity" v-model="order_data.shipping.town" />
                                </div>
                                <div class="field">
                                    <label for="shippingRegion">Region</label>
                                    <input type="text" class="form-control" id="shippingRegion" v-model="order_data.shipping.region" />
                                </div>
                                <div class="field">
                                    <label for="shippingCountry">Country</label>
                                    <select v-model="order_data.shipping.country_code" class="form-control" id="shippingCountry">
                                        <option v-for="country, code in order_data.countries" :value="code">
                                            {{country}}
                                        </option>
                                    </select>
                                </div>
                                <div class="field actions">
                                    <a @click.prevent="updateShipping" class="btn btn-primary btn-sm" href="#">Update</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <h2>Billing <a v-if="!toggle_billing_editor" @click.prevent="toggle_billing_editor = true" class="btn btn-primary btn-sm" href="#" style="font-size: 10px; padding: 0.025em 0.5em; margin-left: 0.5em;">Update</a></h2>
                            <template v-if="!toggle_billing_editor">
                                <div v-if="!billing_missing" class="address-detail">
                                    <p>{{order_data.billing.firstname}} {{order_data.billing.surname}}</p>
                                    <p>{{order_data.billing.email}}</p>
                                    <p>{{order_data.billing.phone}}</p>
                                    <p v-if="order_data.billing.org">{{order_data.billing.org}}</p>
                                    <p>{{order_data.billing.apartment ? (order_data.billing.apartment + ', ') : ''}}{{order_data.billing.address}}</p>
                                    <p>{{order_data.billing.suburb ? `${ order_data.billing.suburb },` : ''}} {{order_data.billing.town}}, {{order_data.billing.region}}</p>
                                    <p>{{order_data.billing.country}}, {{order_data.billing.postcode}}</p>
                                </div>
                                <p v-else>Billing address is missing or incomplete</p>
                            </template>
                            <div v-else class="address-detail__editor">
                                <div class="field form-check">
                                    <input class="form-check-input" type="checkbox" v-model="order_data.billing.same_addr" value="" id="SameBilling">
                                    <label class="form-check-label" for="SameBilling">
                                        Same as shipping
                                    </label>
                                </div>
                                <template v-if="!order_data.billing.same_addr">
                                    <div class="field">
                                        <label for="billingFirstname">First name</label>
                                        <input type="text" class="form-control" id="billingFirstname" v-model="order_data.billing.firstname" />
                                    </div>
                                    <div class="field">
                                        <label for="billingLastname">Last name</label>
                                        <input type="text" class="form-control" id="billingLastname" v-model="order_data.billing.surname" />
                                    </div>
                                    <div class="field">
                                        <label for="billingPhone">Phone</label>
                                        <input type="text" class="form-control" id="billingPhone" v-model="order_data.billing.phone" />
                                    </div>
                                    <div class="field">
                                        <label for="billingOrg">Organisation</label>
                                        <input type="text" class="form-control" id="billingOrg" v-model="order_data.billing.org" />
                                    </div>
                                    <div class="field">
                                        <label for="billingApartment">Apt/Unit/Suite</label>
                                        <input type="text" class="form-control" id="billingApartment" v-model="order_data.billing.apartment" />
                                    </div>
                                    <div class="field">
                                        <label for="billingAddress">Address</label>
                                        <input type="text" class="form-control" id="billingAddress" v-model="order_data.billing.address" />
                                    </div>
                                    <div class="field">
                                        <label for="billingSuburb">Suburb</label>
                                        <input type="text" class="form-control" id="billingSuburb" v-model="order_data.billing.suburb" />
                                    </div>
                                    <div class="field">
                                        <label for="billingCity">City</label>
                                        <input type="text" class="form-control" id="billingCity" v-model="order_data.billing.town" />
                                    </div>
                                    <div class="field">
                                        <label for="billingRegion">Region</label>
                                        <input type="text" class="form-control" id="billingRegion" v-model="order_data.billing.region" />
                                    </div>
                                    <div class="field">
                                        <label for="billingCountry">Country</label>
                                        <select v-model="order_data.billing.country_code" class="form-control" id="billingCountry">
                                            <option v-for="country, code in order_data.countries" :value="code">
                                                {{country}}
                                            </option>
                                        </select>
                                    </div>
                                </template>
                                <div class="field actions">
                                    <a @click.prevent="updateBilling" class="btn btn-primary btn-sm" href="#">Update</a>
                                </div>
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
                        <div v-if="order_data.cart.comment || order_data.cart.giftwrap">
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
