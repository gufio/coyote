import Vue, { VNode } from "vue";
import VueTopic from '../components/forum/topic.vue';
import VuePagination from '../components/pagination.vue';
import VueTimeago from '../plugins/timeago';
import VueAutocomplete from '../components/forms/autocomplete.vue';
import PerfectScrollbar from '../components/perfect-scrollbar';
import store from "../store";
import { Hit, Hits, Sort, SearchOptions } from "../types/hit";
import { Model } from "../types/models";
import axios from 'axios';

type ModelType = {
  [key in Model]: string;
};

interface ForumItem {
  id: number;
  name: string;
  indent: boolean;
}

enum SortOptions {
  'score' = 'Trafność',
  'date' = 'Data'
}

// @todo duplikat z searchbar.vue
const ModelOptions: ModelType = {
  [Model.Topic]: 'Wątki na forum',
  [Model.Job]: 'Oferty pracy',
  [Model.Microblog]: 'Mikroblogi',
  [Model.User]: 'Użytkownicy',
  [Model.Wiki]: 'Artykuły'
}

declare global {
  interface Window {
    hits: Hits;
    model?: Model;
    query: string;
    sort: Sort;
    postsPerPage: number;
    categories: number[];
    forums: ForumItem[];
  }
}

Vue.use(VueTimeago);

Vue.component('vue-result-common', {
  props: {
    hits: {
      type: Array
    }
  },
  methods: {
    title(hit: Hit) {
      return hit.title ? hit.title : (hit.subject ? hit.subject : hit.text);
    }
  },
  template: `
    <ul id="search-results" class="list-unstyled">
      <li v-for="hit in hits">
        <h2 class="mt-4 mb-2 text-truncate"><a :href="hit.url" v-html="title(hit)"></a></h2>

        <div class="mb-2">
          <span class="text-muted"><vue-timeago :datetime="hit.created_at"></vue-timeago></span> <span v-html="hit.text"></span>
        </div>

        <ul v-if="hit.children.length" class="children mt-2 mb-2">
          <li v-for="child in hit.children">
            <a :href="child.url" class="text-truncate" v-html="child.text"></a>
            <vue-timeago :datetime="child.created_at" class="text-muted"></vue-timeago>
          </li>
        </ul>

        <ul class="breadcrumb d-inline-flex p-0">
          <li v-for="breadcrumb in hit.breadcrumbs" class="breadcrumb-item"><a :href="breadcrumb.url">{{ breadcrumb.name }}</a></li>
        </ul>
      </li>
    </ul>`
})

Vue.component('vue-result-topic', {
  props: {
    hits: {
      type: Array
    }
  },
  components: { 'vue-topic': VueTopic },
  render: function(createElement) {
    let items: VNode[] = [];

    this.hits.forEach(hit => items.push(createElement('vue-topic', {props: {topic: hit, postsPerPage: window.postsPerPage, showCategoryName: true }})))

    return createElement('div', { class: 'card card-default card-topics' }, items)
  }
})

new Vue({
  el: '#js-search',
  delimiters: ['${', '}'],
  data: {
    hits: window.hits,
    model: window.model,
    query: window.query,
    sort: window.sort,
    categories: window.categories,
    forums: window.forums,
    defaults: {
      sort: 'score'
    },
    sortOptions: SortOptions,
    modelOptions: ModelOptions,
    users: [],
    user: ''
  },
  components: { 'vue-pagination': VuePagination, 'perfect-scrollbar': PerfectScrollbar, 'vue-autocomplete': VueAutocomplete },
  store,
  created() {
    store.commit('topics/init', window.hits.data || []);
  },

  methods: {
    getComponent() {
      return this.model === Model.Topic ? `vue-result-${this.model.toLowerCase()}` : 'vue-result-common';
    },

    setSort(sort: Sort) {
      this.sort = sort;
      this.request();
    },

    setModel(model?: Model) {
      // before making a request, we must clear results list because on different tabs, data could have different format
      this.hits.data = [];
      this.model = model;

      this.request();
    },

    setUser(user: string) {
      this.user = user;
    },

    toggleCategory(id: number) {
      const index = this.categories.indexOf(id);

      index > -1 ? this.categories.splice(index, 1) : this.categories.push(id);

      this.request();
    },

    modelUrl(model?: Model) {
      let params = { ...this.requestParams, model }

      if (!model) {
        delete params['model'];
      }

      return this.getUrl(params);
    },

    sortUrl(sort: Sort) {
      return this.getUrl({ ...this.requestParams, sort });
    },

    request() {
      history.pushState(this.requestParams, '', this.getUrl(this.requestParams));

      axios.get('/Search', {params: this.requestParams}).then(result => {
        this.hits = result.data;
      });
    },

    getUrl(params: any) {
      return `/Search?${new URLSearchParams(params).toString()}`;
    },

    lookupName(name) {
      if (!name.trim().length) {
        return;
      }

      axios.get('/User/Prompt', {params: {q: name}}).then(response => {
        this.users = response.data.data;
        //
        // if (this.items.length === 1 && this.items[0].name.toLowerCase() === name.toLowerCase()) {
        //   this.items = [];
        // }
      });
    },

  },

  computed: {
    requestParams(): SearchOptions {
      let params = { q: this.query, model: this.model, sort: this.sort, categories: this.categories };

      Object.keys(params).forEach(key => {
        if (!params[key] || (Array.isArray(params[key]) && params[key].length === 0)) {
          delete params[key];
        }
      })

      return params;
    },

    defaultSort(): Sort {
      return this.sort || this.defaults.sort;
    },

    selectedCategories(): string {
      return this
        .categories
        .map(id => {
          const index = this.forums.findIndex(forum => forum.id == id) // == because id can be string

          return this.forums[index].name;
        })
        .splice(0, 5)
        .join(', ');
    }
  },

  watch: {
    'user' (newValue, oldValue) {
      // if (newValue && oldValue && newValue.toLowerCase() === oldValue.toLowerCase()) {
      //   return;
      // }

      this.lookupName(newValue);
    }
  },
});
