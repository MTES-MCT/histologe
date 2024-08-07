const newman = require('newman');
const async = require('async');

const options = {
    collection: require('../histologe/histologe.postman_collection.json'),
    reporters: 'cli'
};

const concurrency = 2; // Nombre de requêtes concurrentes

async.timesLimit(concurrency, concurrency, function(n, next) {
    newman.run(options, function(err, summary) {
        if (err) {
            console.error(err);
        } else {
            console.log(`Completed iteration ${n + 1}`);
        }
        next(err, summary);
    });
}, function(err) {
    if (err) {
        console.error('An error occurred:', err);
    } else {
        console.log('All requests completed');
    }
});
