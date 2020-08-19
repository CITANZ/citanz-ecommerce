<div id="cita-ecom-discount-holder">
    <div id="cita-ecom-discount">
        <input ref="discount_id" type="hidden" value="$DiscountID" />
        <input ref="existings" type="hidden" value="$Existings" />
        <input ref="variants" type="hidden" value="$Variants" />
        <div class="form-group field text">
            <label for="cita-ecom-discount--search" class="form__field-label">Search product</label>
            <div class="form__field-holder">
                <input type="text" @keydown="keydownHandler" v-model="search_term" class="text" id="cita-ecom-discount--search" />
                <ul class="candidates" v-if="candidates && candidates.length > 0">
                    <li v-for="candidate in candidates">
                        <a href="#" @click.prevent="addProduct(candidate.id)">{{ candidate.title }}</a>
                    </li>
                </ul>
                <p v-if="!existings || !existings.length" style="font-size: 12px; margin-top: 0.5em;">Search and add product(s)</p>
            </div>
        </div>
        <div class="form-group field text" v-if="existings && existings.length">
            <label class="form__field-label">Applied to products:</label>
            <div class="form__field-holder">
                <table class="table table-striped">
                    <tbody>
                        <tr v-for="product in existings">
                        <td style="vertical-align: middle;">
                            <strong>{{ product.title }}</strong>
                            <ul>
                                <li v-for="variant in product.variants">
                                    <label :for="`variant-id-${variant.id}`">
                                    <input :id="`variant-id-${variant.id}`" :checked="hasVariant(variant.id)" :value="variant.id" @change="checkVariant" type="checkbox" /> {{ variant.variant_title }}</label>
                                </li>
                            </ul>
                            <p>If no variant is checked, this discount will be applied book-wide (all variants of this book)</p>
                        </td>
                        <td width="10%"><button type="button" class="btn btn-danger" @click.prevent="removeProduct(product.id)">Remvoe</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
