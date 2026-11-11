# 0008: A check's error may name its own location

Status: proposed

## Context
A check is asked about one value, and every error it returned was reported at that value's location. A check on an object that refuses one of its members could not say which member. Drupal's ReviewDeadline constraint, for example, runs on a `deadline` object and refuses its `amount` (`atPath('amount')` in Symfony). The provider had to file the error at `/deadline` and name the member in a param, so a form could not flag the `amount` field.

## Decision
- **An error of a check may carry `location`**: a JSON Pointer relative to the location of the value the check was asked about.
- **The error is reported at the two joined**: `/deadline` + `/amount` = `/deadline/amount`. Without `location`, or with `""`, it is reported where the check was asked, as before.
- **A `location` must name a value present in the instance.** One that is not a JSON Pointer, or names no value present (past the end of a list, a property the value does not hold), is a failed check: the request is reported in `unchecked` with reason `error`, and its errors are dropped. A host maps a location to a field, so a location naming nothing is a provider's mistake, never a refusal.

## Consequences
- A provider files each error where it belongs, and a host maps it to the field it is about.
- Shaping applies as to any error at that location: sorting, de-duplication, the `type` rule, `partial` and `locations`.
- Providers that return no `location` are unchanged.
- The fixture runners pass `location` through from the canned errors (`fixtures/tests.schema.json`).
