<template>
  <component :is="tagName" v-profile="user.id">{{ user.name }}</component>
</template>

<script lang="ts">
  import Vue from 'vue';
  import { default as mixins } from './mixins/user';
  import { Prop } from "vue-property-decorator";
  import { User } from "../types/models";
  import Component from "vue-class-component";

  @Component({
    name: 'user-name',
    mixins: [mixins]
  })
  export default class VueUserName extends Vue {
    @Prop(Object)
    user!: User;

    get tagName() {
      return this.user.is_blocked || this.user.deleted_at ? 'del' : 'a';
    }
  }
</script>

