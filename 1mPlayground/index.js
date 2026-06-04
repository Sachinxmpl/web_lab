import express from 'express';

const app = express();

app.use(express.json())

app.get('/sample', (req, res) => {
  res.status(200).send('Hello World!');
});



app.patch('/update-something/:id/:name', async (req, res) => {
  const { id, name } = req.params;
  const { value1, value2 } = req.query;

  // Body example:
  // {
  //   "items": [1,2,3,4,5]
  // }

  const { items = [] } = req.body || {};

  // Validation
  if (isNaN(Number(id))) {
    return res.status(400).send({
      error: 'id must be a number'
    });
  }

  if (!name || name.length < 3) {
    return res.status(400).send({
      error: 'Name must be at least 3 characters long'
    });
  }

  if (!Array.isArray(items)) {
    return res.status(400).send({
      error: 'items must be an array'
    });
  }

  const formattedValues = [];

  // Simulate heavy processing
  for (const item of items) {
    // fake CPU-heavy work
    let computed = 0;

    for (let i = 0; i < 100000; i++) {
      computed += i % 7;
    }

    formattedValues.push({
      original: item,
      processed: `${name}-${item}`,
      computed
    });
  }

  return res.send({
    success: true,
    id: Number(id),
    name,
    query: {
      value1,
      value2
    },
    totalItems: items.length,
    result: formattedValues
  });
});

app.listen(3000, () => {
  console.log('Server is running on port 3000');
});