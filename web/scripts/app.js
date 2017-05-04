var app = new Vue({
    delimiters: ['${', '}'],
    el: '#app',
    data: {
        message: 'Hello with VueJS',
        cards: []
    },
    mounted: function() {
        console.log('ready');
        this.loadFeed();

        setInterval(function() {
            this.loadFeed();
        }.bind(this), 3000);
    },
    methods: {
        loadFeed: function() {
            var _this = this;
            this.$http.get('/dashboard/feed').then(response => {
                console.log(response.body);
                this.cards = response.body;

        }, response => {
                console.log(response);
            });
        }
    }
});