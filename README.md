# Quick Payments WooCommerce Plugin

A simple WooCommerce plugin that allows merchants to create a Razorpay payment button on any page using a shortcode.

---

## Features

- Easy Razorpay integration
- Add payment button using shortcode `[RZP]`
- Custom payment details via custom fields
- No complex WooCommerce setup required for quick payments

---

## Installation

1. Download the plugin `.zip` file
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin**
4. Upload the `.zip` file and click **Install Now**
5. Click **Activate**

---

## Configuration

1. After activation, navigate to plugin settings
2. Enter the following Razorpay credentials:
   - **Key ID**
   - **Key Secret**
3. Click **Save Changes**

> You can get these credentials from your Razorpay Dashboard.

---

## Usage

### Step 1: Create or Edit a Page

- Go to **Pages → Add New** (or edit an existing page)

---

### Step 2: Add Payment Button

- Add a block and insert the shortcode:

[RZP]

- This will render the Razorpay payment button on the frontend

---

### Step 3: Enable Custom Fields

1. Click the top-right menu (⋮) in the page editor
2. Go to **Preferences → Panels**
3. Enable **Custom Fields**
4. Save preferences and refresh the page

---

### Step 4: Add Required Fields

Scroll down to the **Custom Fields (Meta Boxes)** section and add the following fields:

| Field Name   | Required | Description                     |
|-------------|----------|---------------------------------|
| name        | Yes      | Name of the product/service     |
| description | Yes      | Short description               |
| amount      | Yes      | Payment amount (in smallest currency unit, e.g., paise) |

> Field names must match exactly: `name`, `description`, `amount`

---

### Step 5: Save Page

- Click **Publish** or **Update**

---

### Step 6: Test Payment

- Open the page on the frontend
- Click the Razorpay button
- Payment popup will appear with the configured amount

---

## Example

Custom Fields:

name: Test Product
description: Test payment
amount: 50000

> This will create a payment of ₹500.00

---

## Notes

- Ensure Razorpay credentials are correct
- Amount should be passed in the smallest currency unit (paise)
- Custom field labels are case-sensitive

---

## Troubleshooting

**Payment button not visible?**
- Ensure `[RZP]` shortcode is added correctly
- Verify plugin is activated

**Custom fields not showing?**
- Enable them via **Preferences → Panels → Custom Fields**
- Refresh the editor

**Payment not working?**
- Check Razorpay API keys
- Verify browser console for errors

---

## License

This plugin is intended for internal or commercial use based on your organization’s policy.

---

## Support

For issues or feature requests, please raise a ticket or contact the development team.