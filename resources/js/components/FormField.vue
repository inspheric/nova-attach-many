<template>
    <default-field :field="field" :full-width-content="field.fullWidth" :show-help-text="false">
        <template slot="field" :class="{'border-danger border': hasErrors}">
            <div :class="{'border-danger border': hasErrors}">
                <div v-if="field.showToolbar" class="flex border-b-0 border border-40 relative">
                    <!-- <div v-if="preview" class="flex justify-center items-center absolute pin z-10 bg-white">
                        <h3>{{ __('Selected Items') }} ({{ selected.length  }})</h3>
                    </div> -->
                    <div @click="selectAll" class="w-16 text-center flex justify-center items-center">
                        <fake-checkbox :checked="selectingAll" class="cursor-pointer" :title="__('Select all')"></fake-checkbox>
                    </div>
                    <!-- <div class="flex-1 flex items-center relative">
                        <input v-model="search" type="text" :placeholder="__('Search')" class="form-control form-input form-input-bordered w-full ml-0 m-4">
                        <span v-if="search" @click="clearSearch" class="pin-r font-sans font-bolder absolute pr-8 cursor-pointer text-black hover:text-80">x</span>
                    </div> -->
                </div>
                <div class="border-b-0 border border-40 relative">
                    <div class="form-input-bordered px-1 w-full ml-0 m-4" style="min-height: 2.25rem;">
                        <div class="flex flex-wrap max-h-search overflow-auto">
                            <div v-for="(resource, $index) in selectedResources"
                                :key="$index"
                                :tabindex="(focused($index, 0)) || (focused.length == 0 && $index === 0) ? 0 : -1"
                                :aria-checked="focused($index)"
                                ref="selectedItem"
                                class="flex py-1 px-2 m-1 bg-30 rounded-full select-none cursor-pointer outline-none"
                                :class="{ 'bg-primary text-white': focused($index) }"
                                @click="focus($event, $index)"
                                @focus="focused = [$index]"
                                @blur="focused = []"
                                @keydown.delete.prevent="unselect($event, $index, resource.value)"
                                @keydown.left.prevent="focus($event, $index, -1)"
                                @keydown.right.prevent="focus($event, $index, 1)"
                            >
                                <span>{{ resource.display }}</span>
                                <span @click="unselect($event, $index, resource.value)"
                                    class="font-sans font-bolder pl-2 cursor-pointer"
                                    :class="{
                                        'text-80 hover:text-black': focused($index),
                                        'text-white-50% hover:text-white': focused($index)
                                        }"
                                >x</span>
                            </div>
                            <!--
                            @blur="clearSearch"
                            @input="handleInput"
                            @keydown.enter.prevent="chooseSelected"
                            @keydown.down.prevent="move(1)"
                            @keydown.up.prevent="move(-1)" -->
                            <div
                                tabindex="0"
                                class="py-1 px-2 m-1 bg-danger text-white rounded-full select-none cursor-pointer outline-none hidden"
                                :class="{ 'flex': abandoned }"
                                @click="unabandon"
                            >
                                <span>{{ search }}</span>
                                <span @click="newSearch"
                                    class="font-sans font-bolder pl-2 cursor-pointer text-white-50% hover:text-white"
                                >x</span>
                            </div>
                            <input
                                :disabled="disabled"
                                v-model="search"
                                @keydown.esc.prevent="clearSearch"
                                @keydown.left="focusBack($event)"
                                @keydown.delete="deleteBack($event)"
                                @blur="abandon"
                                @focus="abandoned = false"
                                ref="search"
                                class="outline-none search-input-input px-1 py-1.5 text-sm leading-normal bg-white rounded flex-grow flex-1"
                                :class="{ 'hidden': abandoned }"
                                type="text"
                                spellcheck="false"
                                style="min-width: 2rem;"
                            />
                        </div>
                    </div>
                </div>
                <div class="border border-40 relative overflow-auto" :style="{ height: field.height }">
                    <div v-if="loading" class="flex justify-center items-center absolute pin z-50 bg-white">
                        <loader class="text-60" />
                    </div>
                    <div v-else v-for="resource in resources" :key="resource.value" @click="toggle($event, resource.value)" class="flex py-3 cursor-pointer select-none hover:bg-30">
                        <div class="w-16 flex justify-center">
                            <fake-checkbox :checked="selected.includes(resource.value)" />
                        </div>
                        <span>{{ resource.display }}</span>
                    </div>
                </div>
            </div>

            <help-text class="error-text mt-2 text-danger" v-if="hasErrors">
                {{ firstError }}
            </help-text>

            <div class="help-text mt-3 w-full flex" :class="{ 'invisible': loading }">
                <span v-if="field.showCounts" class="pr-2 border-60 whitespace-no-wrap" :class="{ 'border-r mr-2': field.helpText }">
                    {{ selected.length  }} / {{ available.length }}
                </span>
                <span class="border-60" :class="{'border-r mr-2 pr-2': field.showPreview }">
                    <help-text class="help-text" v-if="field.helpText"> {{ field.helpText }} </help-text>
                </span>
                <span v-if="field.showPreview" @click="togglePreview($event)" class="flex cursor-pointer select-none">
                    <fake-checkbox class="flex" :checked="preview" />
                    <span class="pl-2">{{ __('Show selected only') }}</span>
                </span>
            </div>

        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import Vue from 'vue'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            search: null,
            selected: [],
            selectingAll: false,
            available: [],
            preview: false,
            loading: true,
            focused: [],
            abandoned: false
        }
    },
    methods: {
        setInitialValue() {

            let baseUrl = '/nova-vendor/nova-attach-many/';

            if(this.resourceId) {
                Nova.request(baseUrl + this.resourceName + '/' + this.resourceId + '/attachable/' + this.field.attribute)
                    .then((data) => {
                        this.selected = data.data.selected || []
                        this.available = data.data.available || []
                        this.loading = false;
                    });
            }
            else {
                Nova.request(baseUrl + this.resourceName + '/attachable/' + this.field.attribute)
                    .then((data) => {
                        this.available = data.data.available || []
                        this.loading = false
                    })
            }

        },

        fill(formData) {
            formData.append(this.field.attribute, this.value || [])
        },

        toggle(event, id) {
            this.clearSearch()

            if(this.selected.includes(id)) {
                this.selected = this.selected.filter(selectedId => selectedId != id)
                this.$emit('unselected', id)
            }
            else {
                this.selected.push(id)
                this.$emit('selected', id)
            }
        },

        focus(event, index, offset) {
            if (offset < 0) {
                if (index > 0) {
                    index = index + offset
                    this.$refs.selectedItem[index].focus()
                }
            }
            else if (offset > 0) {
                if (index < this.selected.length - 1) {
                    index = index + offset
                    this.$refs.selectedItem[index].focus()
                }
                else {
                    index = null
                    this.$refs.search.focus()
                }
            }

            this.focused = index
        },

        focused(index, only) {
            if (only) {
                return this.focused == [index]
            }
            return this.focused.includes(index)
        },

        focusBack(event) {
            if(event.target.selectionStart == 0 && this.selected.length > 0) {
                let index = this.selected.length - 1
                this.$refs.selectedItem[index].focus()
                this.focused = index
            }
        },

        deleteBack(event) {
            if((this.search == null || this.search.length == 0) && event.key == 'Backspace' && this.selected.length > 0) {
                this.selected.pop()
            }
        },

        unselect(event, index, id) {
            this.selected = this.selected.filter(selectedId => selectedId != id)
            this.$emit('unselected', id)

            Vue.nextTick(() => {
                if (event.key == 'Backspace' && index > 0) {
                    index = index - 1
                }
                if (index < this.selected.length) {
                    this.$refs.selectedItem[index].focus()
                }
                else {
                    index = null
                    this.$refs.search.focus()
                }

                this.focused = index
            })

        },

        selectAll() {
            this.focused = null
            let selected = this.selected;

            this.selectingAll = ! this.selectingAll;

            // search can return 0 results
            if(this.resources.length == 0) {
                return
            }

            if(this.resources.length == 1 && this.selected == 1)
            {
                this.selected = []
            }

            // add all resources
            if(! this.search && this.selectingAll) {
                selected = []
                this.resources.forEach(resource => {
                    selected.push(resource.value)
                })
            }

            // remove all resources
            if(! this.search && ! this.selectingAll) {
                selected = []
            }

            // append searched resources
            if(this.search && this.selectingAll) {
                this.resources.forEach(resource => {
                    selected.push(resource.value)
                })
            }

            // remove only searched items
            if(this.search && ! this.selectingAll) {

                let excludingIds = []

                this.resources.forEach(resource => {
                    excludingIds.push(resource.value)
                })

                selected = selected.filter(id => excludingIds.includes(id) == false)
            }

            this.selected = selected
            this.$emit('selected', selected)
        },

        clearSearch() {
            this.focused = null
            this.selectingAll = false
            this.search = null
            this.abandoned = false
        },

        newSearch() {
            this.clearSearch()

            Vue.nextTick(() => {
                this.$refs.search.focus()
            })
        },

        checkIfSelectAllIsActive() {

            if(this.resources.length === 0 || this.preview) {
                this.selectingAll = false
                return
            }

            let visibleAndSelected = [];

            this.resources.forEach(resource => {
                if(this.selected.includes(resource.value)) {
                    visibleAndSelected.push(resource.value)
                }
            })

            this.selectingAll = visibleAndSelected.length == this.resources.length
        },

        togglePreview(event) {
            this.focused = null
            this.preview = ! this.preview
        },

        abandon() {
            this.abandoned = this.search && this.search.length > 0
        },

        unabandon() {
            this.abandoned = false

            Vue.nextTick(() => {
                this.$refs.search.focus()
                this.$refs.search.select()
            })
        }
    },
    computed: {
        selectedResources: function() {
            let available = this.available.filter((resource) => {
                return this.selected.includes(resource.value)
            })

            let selected = this.selected.map((id) => {
                return available.find((resource) => {
                    return resource.value == id
                })
            })

            return selected
        },
        resources: function() {
            if(this.preview) {
                return this.available.filter((resource) => {
                    return this.selected.includes(resource.value)
                })
            }

            if(this.search == null) {
                return this.available
            }

            return this.available.filter((resource) => {
                return resource.display.toLowerCase().includes(this.search.toLowerCase())
            });
        },
        hasErrors: function() {
            return this.errors.errors.hasOwnProperty(this.field.attribute)
        },
        firstError: function() {
            return this.errors.errors[this.field.attribute][0]
        }
    },
    watch: {
        'search': {
            handler: function(search) {
                this.checkIfSelectAllIsActive()
            }
        },
        'selected': {
            handler: function (selected) {
                this.value = JSON.stringify(selected)
                this.checkIfSelectAllIsActive()
            },
            deep: true
        }
    }
}
</script>
