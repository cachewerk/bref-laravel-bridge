
# Examples

## Getting started

The [`getting-started`](getting-started/) example contains a `serverless.yml` that:

- Creates a DynamoDB cache table
- Creates a SQS queue
- Injects the `APP_KEY` at runtime
- Stores the app's versioned assets in S3

Be sure to adjust the `service` name and `params`.

It also includes a GitHub Actions workflow to deploy the app and toggle maintenance mode. The actions require the `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` secrets to be set.

## Relay

We like to be transparent on how we're using this package ourselves, so we published the configuration that manages [relay.so](https://relay.so).

__TODO...__
