import apiFetch from "@wordpress/api-fetch";
import { useEffect, useMemo, useState } from "@wordpress/element";
import {
  Button,
  Card,
  CardBody,
  CardHeader,
  Notice,
  PanelBody,
  Spinner,
  TabPanel,
  TextControl,
  ToggleControl,
} from "@wordpress/components";

const restBase = () => window.cloecloudCliSettings?.restUrl || "";

const fetchSettings = () =>
  apiFetch({ path: `${restBase()}/settings` });

const fetchActivity = () =>
  apiFetch({ path: `${restBase()}/activity` });

const saveSettings = (payload) =>
  apiFetch({
    path: `${restBase()}/settings`,
    method: "POST",
    data: payload,
  });

const fetchMacros = () => apiFetch({ path: `${restBase()}/macros` });

const createMacro = (macro) =>
  apiFetch({
    path: `${restBase()}/macros`,
    method: "POST",
    data: macro,
  });

const runMacro = (id) =>
  apiFetch({
    path: `${restBase()}/macros/${id}/run`,
    method: "POST",
  });

const fetchCloudflare = () => apiFetch({ path: `${restBase()}/cloudflare` });

const saveCloudflare = (payload) =>
  apiFetch({
    path: `${restBase()}/cloudflare`,
    method: "POST",
    data: payload,
  });

const runCommand = (commandId, args = {}) =>
  apiFetch({
    path: `${restBase()}/execute`,
    method: "POST",
    data: { command_id: commandId, args },
  });

const App = () => {
  const [settings, setSettings] = useState(null);
  const [activity, setActivity] = useState([]);
  const [macros, setMacros] = useState([]);
  const [cloudflare, setCloudflare] = useState({ api_token: "", zone_id: "" });
  const [notice, setNotice] = useState(null);
  const [macroDraft, setMacroDraft] = useState({
    name: "",
    steps: [{ command: "cache.clear", args: {} }],
  });
  const [refundDraft, setRefundDraft] = useState({
    order_id: "",
    amount: "",
    reason: "",
    restock_items: true,
  });

  useEffect(() => {
    apiFetch.use(apiFetch.createNonceMiddleware(window.cloecloudCliSettings?.nonce));
    Promise.all([fetchSettings(), fetchActivity()])
      .then(([settingsData, activityData]) => {
        setSettings(settingsData);
        setActivity(activityData);
      })
      .catch(() => setNotice({ status: "error", message: "Failed to load settings." }));
  }, []);

  useEffect(() => {
    if (settings?.pro_active) {
      fetchMacros().then((data) => setMacros(Object.values(data || {})));
      fetchCloudflare().then((data) => setCloudflare(data));
    }
  }, [settings?.pro_active]);

  const commandGroups = useMemo(() => {
    const commands = settings?.commands || [];
    return {
      core: commands.filter((command) => command.tier === "free"),
      pro: commands.filter((command) => command.tier === "pro"),
    };
  }, [settings]);

  const handleToggle = (commandId, value) => {
    const nextToggles = { ...(settings?.toggles || {}) };
    nextToggles[commandId] = value;
    setSettings({ ...settings, toggles: nextToggles });
    saveSettings({ toggles: nextToggles }).catch(() =>
      setNotice({ status: "error", message: "Unable to save command settings." })
    );
  };

  const handleCreateMacro = () => {
    createMacro(macroDraft)
      .then((macro) => {
        setMacros((prev) => [...prev, macro]);
        setMacroDraft({ name: "", steps: [{ command: "cache.clear", args: {} }] });
        setNotice({ status: "success", message: "Macro created." });
      })
      .catch(() => setNotice({ status: "error", message: "Macro creation failed." }));
  };

  const handleRunRefund = () => {
    runCommand("woo.refund", refundDraft)
      .then(() => setNotice({ status: "success", message: "Refund requested." }))
      .catch(() => setNotice({ status: "error", message: "Refund failed." }));
  };

  if (!settings) {
    return (
      <div className="cc-app__loading">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="cc-app">
      <header className="cc-app__hero">
        <div>
          <p className="cc-app__eyebrow">Cloecloud CLI</p>
          <h1>Command Palette Operations</h1>
          <p className="cc-app__subtext">
            Manage commands, automate workflows, and monitor activity from one
            command center.
          </p>
        </div>
        <div className="cc-app__metrics">
          <div>
            <span>Total Commands</span>
            <strong>{settings.metrics?.total_commands || 0}</strong>
          </div>
          <div>
            <span>Pro Status</span>
            <strong>{settings.pro_active ? "Active" : "Locked"}</strong>
          </div>
        </div>
      </header>

      {notice && (
        <Notice
          status={notice.status}
          isDismissible={true}
          onRemove={() => setNotice(null)}
        >
          {notice.message}
        </Notice>
      )}

      <TabPanel
        className="cc-app__tabs"
        tabs={[
          { name: "overview", title: "Overview" },
          { name: "commands", title: "Command Manager" },
          { name: "builder", title: "Custom Macro Builder" },
          { name: "access", title: "Access & Security" },
          { name: "settings", title: "Global Settings" },
        ]}
      >
        {(tab) => {
          if (tab.name === "overview") {
            return (
              <div className="cc-app__section">
                <Card>
                  <CardHeader>Live Feed</CardHeader>
                  <CardBody>
                    <div className="cc-app__feed">
                      {activity.length === 0 ? (
                        <p>No recent activity.</p>
                      ) : (
                        activity.map((entry) => (
                          <div key={entry.id} className="cc-app__feed-item">
                            <span>{entry.label}</span>
                            <span className={`cc-app__status cc-${entry.status}`}>
                              {entry.status}
                            </span>
                          </div>
                        ))
                      )}
                    </div>
                  </CardBody>
                </Card>
                <Card>
                  <CardHeader>Performance Metrics</CardHeader>
                  <CardBody>
                    <p>Tracking command execution volume and automation impact.</p>
                    <div className="cc-app__metric-grid">
                      <div>
                        <strong>{settings.metrics?.total_commands || 0}</strong>
                        <span>Commands executed</span>
                      </div>
                      <div>
                        <strong>32%</strong>
                        <span>Estimated time saved</span>
                      </div>
                      <div>
                        <strong>Peak 14:00</strong>
                        <span>Most active hour</span>
                      </div>
                    </div>
                  </CardBody>
                </Card>
              </div>
            );
          }

          if (tab.name === "commands") {
            return (
              <div className="cc-app__section">
                <Card>
                  <CardHeader>Core Commands</CardHeader>
                  <CardBody>
                    {commandGroups.core.map((command) => (
                      <ToggleControl
                        key={command.id}
                        label={command.label}
                        checked={settings.toggles?.[command.id] !== false}
                        onChange={(value) => handleToggle(command.id, value)}
                      />
                    ))}
                  </CardBody>
                </Card>
                <Card>
                  <CardHeader>WooCommerce & Agency Ops</CardHeader>
                  <CardBody>
                    <div className={settings.pro_active ? "" : "cc-app__locked"}>
                      {!settings.pro_active && (
                        <div className="cc-app__blur">
                          <p>Upgrade to enable WooCommerce macros and agency ops.</p>
                          <Button variant="primary">Upgrade to Pro</Button>
                        </div>
                      )}
                      {commandGroups.pro.map((command) => (
                        <ToggleControl
                          key={command.id}
                          label={command.label}
                          checked={settings.toggles?.[command.id] !== false}
                          onChange={(value) => handleToggle(command.id, value)}
                          disabled={!settings.pro_active}
                        />
                      ))}
                    </div>
                  </CardBody>
                </Card>
                <Card>
                  <CardHeader>WooCommerce Refunds</CardHeader>
                  <CardBody>
                    <div className={settings.pro_active ? "" : "cc-app__locked"}>
                      {!settings.pro_active && (
                        <div className="cc-app__blur">
                          <p>Unlock WooCommerce workflows.</p>
                          <Button variant="primary">Upgrade to Pro</Button>
                        </div>
                      )}
                      <TextControl
                        label="Order ID"
                        value={refundDraft.order_id}
                        onChange={(value) =>
                          setRefundDraft({ ...refundDraft, order_id: value })
                        }
                        disabled={!settings.pro_active}
                      />
                      <TextControl
                        label="Amount (leave blank for full refund)"
                        value={refundDraft.amount}
                        onChange={(value) =>
                          setRefundDraft({ ...refundDraft, amount: value })
                        }
                        disabled={!settings.pro_active}
                      />
                      <TextControl
                        label="Reason"
                        value={refundDraft.reason}
                        onChange={(value) =>
                          setRefundDraft({ ...refundDraft, reason: value })
                        }
                        disabled={!settings.pro_active}
                      />
                      <ToggleControl
                        label="Restock items"
                        checked={refundDraft.restock_items}
                        onChange={(value) =>
                          setRefundDraft({ ...refundDraft, restock_items: value })
                        }
                        disabled={!settings.pro_active}
                      />
                      <Button
                        variant="primary"
                        onClick={handleRunRefund}
                        disabled={!settings.pro_active}
                      >
                        Run Refund
                      </Button>
                    </div>
                  </CardBody>
                </Card>
              </div>
            );
          }

          if (tab.name === "builder") {
            return (
              <div className="cc-app__section">
                <Card>
                  <CardHeader>Active Macros</CardHeader>
                  <CardBody>
                    <div className={settings.pro_active ? "" : "cc-app__locked"}>
                      {!settings.pro_active && (
                        <div className="cc-app__blur">
                          <p>Upgrade to unlock the visual macro builder.</p>
                          <Button variant="primary">Upgrade to Pro</Button>
                        </div>
                      )}
                      {macros.length === 0 ? (
                        <p>No macros created yet.</p>
                      ) : (
                        macros.map((macro) => (
                          <div key={macro.id} className="cc-app__macro">
                            <div>
                              <strong>{macro.name}</strong>
                              <span>{macro.steps.length} steps</span>
                            </div>
                            <Button
                              variant="secondary"
                              onClick={() => runMacro(macro.id)}
                              disabled={!settings.pro_active}
                            >
                              Run
                            </Button>
                          </div>
                        ))
                      )}
                    </div>
                  </CardBody>
                </Card>

                <Card>
                  <CardHeader>Visual Builder Workspace</CardHeader>
                  <CardBody>
                    <div className={settings.pro_active ? "" : "cc-app__locked"}>
                      {!settings.pro_active && (
                        <div className="cc-app__blur">
                          <p>Visual macro builder is a Pro feature.</p>
                          <Button variant="primary">Upgrade to Pro</Button>
                        </div>
                      )}
                      <TextControl
                        label="Macro Name"
                        value={macroDraft.name}
                        onChange={(value) => setMacroDraft({ ...macroDraft, name: value })}
                        disabled={!settings.pro_active}
                      />
                      <TextControl
                        label="Step Command"
                        help="Example: cache.clear or maintenance.toggle"
                        value={macroDraft.steps[0].command}
                        onChange={(value) =>
                          setMacroDraft({
                            ...macroDraft,
                            steps: [{ ...macroDraft.steps[0], command: value }],
                          })
                        }
                        disabled={!settings.pro_active}
                      />
                      <Button
                        variant="primary"
                        onClick={handleCreateMacro}
                        disabled={!settings.pro_active}
                      >
                        Save Macro
                      </Button>
                    </div>
                  </CardBody>
                </Card>

                <Card>
                  <CardHeader>Webhooks & Aliases</CardHeader>
                  <CardBody>
                    <p>Webhook triggers and aliases are managed in Pro.</p>
                  </CardBody>
                </Card>
              </div>
            );
          }

          if (tab.name === "access") {
            return (
              <div className="cc-app__section">
                <Card>
                  <CardHeader>Role-Based Permissions</CardHeader>
                  <CardBody>
                    <div className={settings.pro_active ? "" : "cc-app__locked"}>
                      {!settings.pro_active && (
                        <div className="cc-app__blur">
                          <p>Role-based access control is available in Pro.</p>
                          <Button variant="primary">Upgrade to Pro</Button>
                        </div>
                      )}
                      <p>Define which roles can access destructive commands.</p>
                    </div>
                  </CardBody>
                </Card>
                <Card>
                  <CardHeader>Context-Aware Settings</CardHeader>
                  <CardBody>
                    <div className={settings.pro_active ? "" : "cc-app__locked"}>
                      {!settings.pro_active && (
                        <div className="cc-app__blur">
                          <p>Context-aware filtering is a Pro feature.</p>
                          <Button variant="primary">Upgrade to Pro</Button>
                        </div>
                      )}
                      <p>Restrict commands to specific admin screens.</p>
                    </div>
                  </CardBody>
                </Card>
              </div>
            );
          }

          if (tab.name === "settings") {
            return (
              <div className="cc-app__section">
                <Card>
                  <CardHeader>General & UI Configuration</CardHeader>
                  <CardBody>
                    <PanelBody title="Keyboard Shortcut" initialOpen={true}>
                      <TextControl
                        label="Default Shortcut"
                        value={settings.ui?.shortcut || ""}
                        onChange={(value) =>
                          saveSettings({ ui: { ...settings.ui, shortcut: value } })
                        }
                      />
                    </PanelBody>
                    <PanelBody title="Retention" initialOpen={false}>
                      <TextControl
                        label="Activity Log Retention (days)"
                        value={settings.ui?.retention || ""}
                        onChange={(value) =>
                          saveSettings({ ui: { ...settings.ui, retention: value } })
                        }
                      />
                    </PanelBody>
                  </CardBody>
                </Card>
                <Card>
                  <CardHeader>Cloudflare</CardHeader>
                  <CardBody>
                    <div className={settings.pro_active ? "" : "cc-app__locked"}>
                      {!settings.pro_active && (
                        <div className="cc-app__blur">
                          <p>Cloudflare integration is a Pro feature.</p>
                          <Button variant="primary">Upgrade to Pro</Button>
                        </div>
                      )}
                      <TextControl
                        label="API Token"
                        value={cloudflare.api_token || ""}
                        onChange={(value) =>
                          setCloudflare({ ...cloudflare, api_token: value })
                        }
                        disabled={!settings.pro_active}
                      />
                      <TextControl
                        label="Zone ID"
                        value={cloudflare.zone_id || ""}
                        onChange={(value) =>
                          setCloudflare({ ...cloudflare, zone_id: value })
                        }
                        disabled={!settings.pro_active}
                      />
                      <div className="cc-app__actions">
                        <Button
                          variant="primary"
                          onClick={() => saveCloudflare(cloudflare)}
                          disabled={!settings.pro_active}
                        >
                          Save Cloudflare Settings
                        </Button>
                        <Button
                          variant="secondary"
                          onClick={() => runCommand("cloudflare.purge")}
                          disabled={!settings.pro_active}
                        >
                          Purge Cache
                        </Button>
                      </div>
                    </div>
                  </CardBody>
                </Card>
                <Card>
                  <CardHeader>White-Labeling & Licensing</CardHeader>
                  <CardBody>
                    <div className={settings.pro_active ? "" : "cc-app__locked"}>
                      {!settings.pro_active && (
                        <div className="cc-app__blur">
                          <p>White-labeling and licensing are unlocked in Pro.</p>
                          <Button variant="primary">Upgrade to Pro</Button>
                        </div>
                      )}
                      <p>Configure brand and licensing settings in the Pro module.</p>
                    </div>
                  </CardBody>
                </Card>
              </div>
            );
          }

          return null;
        }}
      </TabPanel>
    </div>
  );
};

const target = document.getElementById("cc-app");
if (target) {
  wp.element.render(<App />, target);
}
