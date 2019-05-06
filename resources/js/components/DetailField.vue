<template>
    <panel-item :field="field">
        <template slot="value">
            <div v-if="field.related" :class="{ 'flex items-start flex-wrap': field.chips }">
                <span v-for="(item, $index) in limitedItems">
                    <router-link
                        v-if="item.viewable && item.display"
                        :to="{
                            name: 'detail',
                            params: {
                                resourceName: field.resourceName,
                                resourceId: item.value,
                            },
                        }"
                        class="dim no-underline"
                        :class="{
                            'flex items-center m-1 pr-3 bg-30 select-none cursor-pointer text-80': field.chips,
                            'rounded-full': field.chips === true,
                            'rounded': field.chips == 'square',
                            'py-1 pl-2': field.chips && !item.avatar,
                            'font-bold text-primary': !field.chips
                        }">
                        <div v-if="field.chips && item.avatar" class="m-px mr-2">
                            <img :src="item.avatar" class="w-6 h-6 block" :class="{
                                'rounded-full': field.chips === true,
                                'rounded': field.chips == 'square'
                            }" />
                        </div>
                        {{ item.display }}<!--
                        --></router-link><!--
                    --><span v-else-if="item.display" class="font-bold">{{ item.display }}</span><!--
                    --><span v-if="!field.chips && $index < limitedItems.length - 1">, </span>
                </span><!--
            --><span v-if="!field.chips && isLimited && !expanded">, </span><!--
            --><a
                    v-if="isLimited"
                    @click="toggle"
                    class="cursor-pointer"
                    :class="{
                        'text-primary dim': !field.chips, 'block mt-6 font-bold': !field.chips && expanded, 'inline-block': !field.chips && !expanded,
                        'text-80 py-1 px-2 m-1 bg-white border border-50 select-none cursor-pointer': field.chips,
                        'rounded-full': field.chips === true,
                        'rounded': field.chips == 'square',
                        }"
                    tabindex="0"
                    @keyup.enter="toggle"
                    @keyup.space="toggle"
                    aria-role="button"
                >{{ showHideLabel }}</a>
            </div>
            <p v-else>&mdash;</p>
        </template>
    </panel-item>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

    data: () => ({
        expanded: false
    }),

    methods: {
        toggle() {
            this.expanded = !this.expanded
        },
    },

    computed: {
        limitedItems() {
            let related = this.field.related || []

            if (this.field.limit && !this.expanded && this.field.limit > 0) {
                related = related.slice(0, this.field.limit)
            }

            return related
        },

        isLimited() {
            return this.field.limit && this.field.limit > 0 && this.field.related.length > this.field.limit
        },

        showHideLabel() {
            return !this.expanded ? this.__('and :count more...', { count: this.field.related.length - this.field.limit }) : this.__('Show Less')
        },
    }
}
</script>
