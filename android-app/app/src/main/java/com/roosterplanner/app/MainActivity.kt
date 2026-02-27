package com.roosterplanner.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.*
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.lifecycle.lifecycleScope
import com.roosterplanner.app.data.ApiClient
import com.roosterplanner.app.data.AuthConfig
import com.roosterplanner.app.data.ConfigStore
import com.roosterplanner.app.data.MeResponse
import com.roosterplanner.app.data.RoosterPlannerRepository
import com.roosterplanner.app.data.ScheduleItem
import com.roosterplanner.app.ui.screens.LoginScreen
import com.roosterplanner.app.ui.screens.ScheduleScreen
import com.roosterplanner.app.ui.theme.RoosterPlannerTheme
import kotlinx.coroutines.launch
import java.time.LocalDate
import java.time.format.DateTimeFormatter

class MainActivity : ComponentActivity() {
    private val configStore by lazy { ConfigStore(this) }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        setContent {
            RoosterPlannerTheme {
                val snackbarHostState = remember { SnackbarHostState() }
                var config by remember { mutableStateOf<AuthConfig?>(null) }
                var repo by remember { mutableStateOf<RoosterPlannerRepository?>(null) }
                var me by remember { mutableStateOf<MeResponse?>(null) }
                var schedules by remember { mutableStateOf<List<ScheduleItem>>(emptyList()) }
                var loading by remember { mutableStateOf(false) }
                var error by remember { mutableStateOf<String?>(null) }
                val scope = rememberCoroutineScope()

                // Restore saved config from DataStore
                LaunchedEffect(Unit) {
                    configStore.config.collect { stored ->
                        config = stored
                        if (stored != null) {
                            connectAndLoad(stored, snackbarHostState) { repository ->
                                repo = repository
                            } { response ->
                                me = response
                            } { items ->
                                schedules = items
                            } { isLoading ->
                                loading = isLoading
                            } { err ->
                                error = err
                            }
                        }
                    }
                }

                Scaffold(snackbarHost = { SnackbarHost(snackbarHostState) }) { padding ->
                    if (config == null) {
                        LoginScreen(
                            initialConfig = null,
                            loading = loading,
                            error = error,
                            onConnect = { cfg ->
                                config = cfg
                                scope.launch { configStore.save(cfg) }
                                connectAndLoad(cfg, snackbarHostState) { repository ->
                                    repo = repository
                                } { response ->
                                    me = response
                                } { items ->
                                    schedules = items
                                } { isLoading ->
                                    loading = isLoading
                                } { err ->
                                    error = err
                                }
                            }
                        )
                    } else if (me != null) {
                        ScheduleScreen(
                            me = me!!,
                            schedules = schedules,
                            onRefresh = {
                                val now = LocalDate.now()
                                val formatter = DateTimeFormatter.ofPattern("yyyy-MM-dd")
                                val start = now.format(formatter)
                                val end = now.plusDays(21).format(formatter)
                                scope.launch {
                                    loading = true
                                    val result = repo?.fetchSchedules(start, end)
                                    loading = false
                                    result?.onSuccess { schedules = it }?.onFailure {
                                        error = it.message
                                    }
                                }
                            },
                            onLogout = {
                                scope.launch { configStore.clear() }
                                config = null
                                me = null
                                schedules = emptyList()
                                repo = null
                                error = null
                            }
                        )
                    } else {
                        // Config exists but we haven't fetched user yet
                        LoginScreen(
                            initialConfig = config,
                            loading = loading,
                            error = error,
                            onConnect = { cfg ->
                                scope.launch { configStore.save(cfg) }
                                connectAndLoad(cfg, snackbarHostState) { repository ->
                                    repo = repository
                                } { response ->
                                    me = response
                                } { items ->
                                    schedules = items
                                } { isLoading ->
                                    loading = isLoading
                                } { err ->
                                    error = err
                                }
                            }
                        )
                    }
                }
            }
        }
    }

    private fun connectAndLoad(
        cfg: AuthConfig,
        snackbarHostState: SnackbarHostState,
        onRepo: (RoosterPlannerRepository) -> Unit,
        onMe: (MeResponse) -> Unit,
        onSchedules: (List<ScheduleItem>) -> Unit,
        onLoading: (Boolean) -> Unit,
        onError: (String?) -> Unit
    ) {
        val repo = RoosterPlannerRepository(ApiClient.build(cfg, enableLogging = true))
        onRepo(repo)
        val scope = lifecycleScope
        scope.launch {
            onLoading(true)
            onError(null)
            val meResult = repo.fetchMe()
            meResult.onSuccess { me ->
                onMe(me)
                val formatter = DateTimeFormatter.ofPattern("yyyy-MM-dd")
                val start = LocalDate.now().format(formatter)
                val end = LocalDate.now().plusDays(21).format(formatter)
                val scheduleResult = repo.fetchSchedules(start, end)
                scheduleResult.onSuccess { onSchedules(it) }
                    .onFailure { onError(it.message) }
            }.onFailure { e ->
                onError(e.message ?: "Inloggen mislukt")
                snackbarHostState.showSnackbar("Verbinding mislukt: ${e.message}")
            }
            onLoading(false)
        }
    }
}
