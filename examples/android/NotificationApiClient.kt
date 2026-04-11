/**
 * Integração com a FCM Notification API — Android / Kotlin
 *
 * Dependências (build.gradle):
 *   implementation("com.squareup.retrofit2:retrofit:2.9.0")
 *   implementation("com.squareup.retrofit2:converter-gson:2.9.0")
 *   implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")
 *
 * Permissão no AndroidManifest.xml:
 *   <uses-permission android:name="android.permission.INTERNET" />
 */

import com.google.gson.annotations.SerializedName
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.*

// -------------------------------------------------------------------
// Data classes
// -------------------------------------------------------------------

data class RegisterTokenRequest(
    @SerializedName("fcm_token") val fcmToken: String,
    @SerializedName("platform")  val platform: String,  // android | ios | web
    @SerializedName("user_id")   val userId: String?    = null,
    @SerializedName("extra")     val extra: Map<String, String>? = null,
)

data class RemoveTokenRequest(
    @SerializedName("fcm_token") val fcmToken: String,
)

data class SendToTokenRequest(
    @SerializedName("fcm_token") val fcmToken: String,
    @SerializedName("title")     val title: String,
    @SerializedName("body")      val body: String,
    @SerializedName("data")      val data: Map<String, String>? = null,
)

data class SendToUserRequest(
    @SerializedName("user_id") val userId: String,
    @SerializedName("title")   val title: String,
    @SerializedName("body")    val body: String,
    @SerializedName("data")    val data: Map<String, String>? = null,
)

data class ApiResponse<T>(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String,
    @SerializedName("data")    val data: T?,
)

data class TokenResponseData(
    @SerializedName("id") val id: Int,
)

data class NotificationResponseData(
    @SerializedName("message_id") val messageId: String?,
)

// -------------------------------------------------------------------
// Retrofit interface
// -------------------------------------------------------------------

interface NotificationApiService {

    @POST("api/tokens")
    suspend fun registerToken(
        @Body request: RegisterTokenRequest,
    ): ApiResponse<TokenResponseData>

    @HTTP(method = "DELETE", path = "api/tokens", hasBody = true)
    suspend fun removeToken(
        @Body request: RemoveTokenRequest,
    ): ApiResponse<Any>

    @POST("api/notifications/send-to-token")
    suspend fun sendToToken(
        @Body request: SendToTokenRequest,
    ): ApiResponse<NotificationResponseData>

    @POST("api/notifications/send-to-users")
    suspend fun sendToUser(
        @Body request: SendToUserRequest,
    ): ApiResponse<Any>
}

// -------------------------------------------------------------------
// Client
// -------------------------------------------------------------------

object NotificationApiClient {

    private const val BASE_URL = "https://sua-api.com/"
    private const val API_KEY  = "sua-chave-secreta"

    val service: NotificationApiService by lazy {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        val http = OkHttpClient.Builder()
            .addInterceptor(logging)
            .addInterceptor { chain ->
                val request = chain.request().newBuilder()
                    .addHeader("Authorization", "Bearer $API_KEY")
                    .addHeader("Accept", "application/json")
                    .build()
                chain.proceed(request)
            }
            .build()

        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(http)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(NotificationApiService::class.java)
    }
}

// -------------------------------------------------------------------
// Exemplos de uso (ViewModel / Repository)
// -------------------------------------------------------------------

class NotificationRepository {

    private val api = NotificationApiClient.service

    /**
     * Chame isto logo após receber o token FCM do Firebase.
     *
     * Exemplo: no onNewToken() do FirebaseMessagingService
     * ou após login do usuário.
     */
    suspend fun registerDeviceToken(fcmToken: String, userId: String) {
        api.registerToken(
            RegisterTokenRequest(
                fcmToken = fcmToken,
                platform = "android",
                userId   = userId,
                extra    = mapOf(
                    "app_version" to BuildConfig.VERSION_NAME,
                    "os_version"  to android.os.Build.VERSION.RELEASE,
                )
            )
        )
    }

    /** Chame ao fazer logout para desregistrar o dispositivo. */
    suspend fun removeDeviceToken(fcmToken: String) {
        api.removeToken(RemoveTokenRequest(fcmToken))
    }

    /** Envia notificação para um token específico (uso interno/admin). */
    suspend fun sendToToken(fcmToken: String, title: String, body: String) {
        api.sendToToken(
            SendToTokenRequest(
                fcmToken = fcmToken,
                title    = title,
                body     = body,
                data     = mapOf("action" to "open_home"),
            )
        )
    }
}

// -------------------------------------------------------------------
// FirebaseMessagingService — onde registrar o token automaticamente
// -------------------------------------------------------------------

/*
class MyFirebaseMessagingService : FirebaseMessagingService() {

    override fun onNewToken(token: String) {
        super.onNewToken(token)

        // Token foi renovado — registrar na API
        val userId = // obter do SharedPreferences ou sessão
        if (userId != null) {
            CoroutineScope(Dispatchers.IO).launch {
                NotificationRepository().registerDeviceToken(token, userId)
            }
        }
    }
}
*/
