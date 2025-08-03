const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement } = window.wp.element;
const { __ } = window.wp.i18n;
const { decodeEntities } = window.wp.htmlEntities;

const settings = window.wc.wcSettings.getSetting("paylis_data", {});

const Label = ({ components }) => {
  return createElement(components.PaymentMethodLabel, {
    text: decodeEntities(settings.title || ""),
  });
};

const Content = () => {
  const description = decodeEntities(settings.description || "");
  return createElement(
    "div",
    { style: { padding: "10px 0", color: "#666" } },
    description
  );
};

registerPaymentMethod({
  name: "paylis",
  label: createElement(Label),
  content: createElement(Content),
  edit: createElement(Content),
  canMakePayment: () =>
    settings.api_key === "configured" && settings.wallet_address !== "",
  ariaLabel: decodeEntities(settings.title || "Paylis Gateway"),
  supports: { features: settings.supports || ["products"] },
});
