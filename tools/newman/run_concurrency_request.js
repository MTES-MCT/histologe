const newman = require('newman');

const options = {
    collection: require('../histologe/histologe.postman_collection.json'),
    reporters: 'cli'
};

const nbConcurrencyRequest = parseInt(process.argv[2].split('=')[1]) || 1;
console.log(`${nbConcurrencyRequest} concurrency calls`);

const runNewman = (iteration) => {
    return new Promise((resolve, reject) => {
        newman.run(options, function (err, summary) {
            if (err) {
                console.error(err);
                reject(err);
            } else {
                console.log(`Completed iteration ${iteration + 1}`);
                resolve(summary);
            }
        });
    });
};

const runAllRequests = async (concurrency) => {
    const tasks = [];
    for (let i = 0; i < concurrency; i++) {
        tasks.push(runNewman(i));
    }

    try {
        await Promise.all(tasks);
        console.log('All requests completed');
    } catch (err) {
        console.error('An error occurred:', err);
    }
};

runAllRequests(nbConcurrencyRequest).then(() => console.log('Done!'));